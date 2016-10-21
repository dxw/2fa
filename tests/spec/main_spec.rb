require 'httparty'
require 'nokogiri'
require 'mysql'
require 'rotp'
require 'base32'

class Http
  include HTTParty
  base_uri 'http://localhost:8910/'
  follow_redirects false

  def self::timed_post(*args)
    t = Time.now
    ret = self::post(*args)
    elapsed = Time.now - t
    puts
    puts "#{args} - #{elapsed}s"
    return ret
  end
end

# Takes a response, outputs the cookies as they should appear in the Cookie header of a request
def extract_cookies(response)
  response.headers.get_fields('Set-Cookie').map {|a| a.split(';')[0].split('=')}.reduce({}) {|m,a| m[a[0]] = a[1]; m}
end

def format_cookies(m)
  m.map{|a,b| a+'='+b}.join('; ')
end

RSpec.configure do |config|
  config.expect_with :rspec do |c|
    c.syntax = [:expect, :should]
  end
end

def set_override(value)
  # For all users
  @mysql.query("UPDATE wp_usermeta SET meta_value='"+value+"' WHERE meta_key='2fa_override'")
end

def set_devices(value)
  # For all users
  @mysql.query("UPDATE wp_usermeta SET meta_value='"+value+"' WHERE meta_key='2fa_devices'")
end

def set_devices_random_totp
  @secret = generate_secret
  set_override('yes')
  set_devices('a:1:{i:0;a:3:{s:4:"mode";s:4:"totp";s:4:"name";s:6:"meowyy";s:6:"secret";s:16:"'+@secret+'";}}')
end

def generate_secret
  Base32.random_base32(16)
end

def login(user='admin')
  # Store the test cookie
  req = Http::get('/wp-login.php')
  req.response.code.should == '200'
  @cookies.merge! extract_cookies(req)

  # Log in
  req = Http::post(
    '/wp-login.php',
    body: {
      log: user,
      pwd: 'foobar',
    },
    headers: {'Cookie' => format_cookies(@cookies)},
  )
  @cookies.merge! extract_cookies(req)
  if req.response.code === '200'
    # extract nonce & user_id
    map = req.response.body.split("\n").select{|l| l.match(/nonce|user_id/)}.map{|l| l.match(/\A\s*<input type="hidden" name="(.*)" value="(.*)">\s*\Z/)[1,2]}.reduce({}){|m,a| m[a[0]] = a[1]; m}
    @nonce = map['nonce']
    @user_id = map['user_id']
  end
end

def login_2nd_step(token, params={})
  response = Http::post(
    '/wp-login.php',
    body: {
      token: token,
      user_id: @user_id,
      nonce: @nonce,
      rememberme: 'no',
      redirect_to: '',
    }.merge(params),
    headers: {'Cookie' => format_cookies(@cookies)},
  )
  @cookies.merge! extract_cookies(response)
end

def loggedin?
  # Verify that we're logged in
  response = Http::get(
    '/wp-admin/',
    headers: {'Cookie' => format_cookies(@cookies)},
  )
  response.response.code == '200'
end

def logout
  req = Http::get(
    '/wp-login.php?action=logout',
    headers: {'Cookie' => format_cookies(@cookies)},
  )
  req.response.code == '403'
  nonce = req.response.body.match(/_wpnonce=([0-9a-f]+)\W/)[1]

  req = Http::get(
    '/wp-login.php?action=logout&_wpnonce='+nonce,
    headers: {'Cookie' => format_cookies(@cookies)},
  )
  req.response.code == '302'
  @cookies.merge! extract_cookies(req)
end

describe "2FA" do
  before :all do
    host = ENV['MYSQL_HOST'] || 'localhost'
    user = ENV['MYSQL_USER'] || 'root'
    password = ENV['MYSQL_PASSWORD'] || ''
    db = ENV['MYSQL_DB'] || '2fa_test'
    @mysql = Mysql.new(host, user, password)

    # Get WP if we don't already have it
    system("test -f latest.zip || wget http://wordpress.org/latest.zip").should be_truthy
    system("test -d wordpress || unzip latest.zip").should be_truthy
    system("rm -rf wordpress/wp-content/plugins/2fa && mkdir -p wordpress/wp-content/plugins/2fa").should be_truthy
    %w[2fa.php lib vendor.phar views src].each do |f|
      system("cp -R ../#{f} wordpress/wp-content/plugins/2fa/").should be_truthy
    end

    # Set up DB
    @mysql.query("DROP DATABASE IF EXISTS "+db)
    @mysql.query("CREATE DATABASE "+db)
    @mysql.query("USE "+db)
    system("rm -f wordpress/wp-config.php").should be_truthy
    pwd = password == '' ? '' : "--dbpass=#{password}"
    system("wp --allow-root --path=wordpress/ core config --dbname=#{db} --dbuser=#{user} #{pwd} --dbhost=#{host}").should be_truthy
    system("wp --allow-root --path=wordpress/ core multisite-install --url=http://localhost:8910/ --title=Test --admin_user=admin --admin_email=tom@dxw.com --admin_password=foobar").should be_truthy
    system("wp --allow-root --path=wordpress/ plugin activate 2fa").should be_truthy
    system("wp --allow-root --path=wordpress/ user create editor editor@local.local --role=editor --user_pass=foobar")

    # Set basic options to be overwritten in tests
    @mysql.query("INSERT INTO wp_usermeta SET user_id=1, meta_key='2fa_override', meta_value='no'")
    @mysql.query("INSERT INTO wp_usermeta SET user_id=1, meta_key='2fa_devices', meta_value=''")
    @mysql.query("INSERT INTO wp_usermeta SET user_id=2, meta_key='2fa_override', meta_value='no'")
    @mysql.query("INSERT INTO wp_usermeta SET user_id=2, meta_key='2fa_devices', meta_value=''")

    # Start WP
    @wp_proc = fork do
      exec 'php -d sendmail_path=/bin/false -S localhost:8910 -t wordpress/'
    end
    Process.detach(@wp_proc)
    sleep(5)
  end

  after :all do
    # Stop WP
    Process.kill('TERM', @wp_proc)
  end

  before :each do
    set_override('no')
    set_devices('')
    @cookies = {}
  end

  # Tests

  describe "basic login process" do
    describe "without 2FA" do
      it "allows login" do
        set_override('no')
        set_devices('a:1:{i:0;a:3:{s:4:"mode";s:4:"totp";s:4:"name";s:6:"meowyy";s:6:"secret";s:16:"5AZOON3OUHEDGA3H";}}')
        login
        loggedin?.should == true
      end
    end

    describe "with 2FA" do
      it "allows login but redirects to setup" do
        set_override('yes')
        set_devices('')

        login

        response = Http::get(
          '/wp-admin/',
          headers: {'Cookie' => format_cookies(@cookies)},
        )
        response.response.code.should == '302'
        response.headers.get_fields('Location').should == ['http://localhost:8910/wp-admin/users.php?page=2fa&step=setup']
      end

      it "disallows login with devices set" do
        set_devices_random_totp

        login
        loggedin?.should == false
      end

    end
  end

  describe "login with TOTP" do
    it "disallows login with incorrect TOTP token" do
      set_devices_random_totp

      login
      # there's a one in a million chance of this succeeding
      login_2nd_step('000000')
      loggedin?.should == false
    end

    it "allows login with correct TOTP token" do
      set_devices_random_totp

      login
      totp = ROTP::TOTP.new(@secret)
      login_2nd_step(totp.now)
      loggedin?.should == true
    end
  end

  describe "skippping" do
    it "requires a token every time if the checkbox is not checked" do
      set_devices_random_totp

      login
      totp = ROTP::TOTP.new(@secret)
      login_2nd_step(totp.now)
      loggedin?.should == true

      logout

      login
      loggedin?.should == false

      login_2nd_step(totp.at(Time.now + 30))
      loggedin?.should == true
    end

    it "allows logging in without token" do
      set_devices_random_totp

      login
      totp = ROTP::TOTP.new(@secret)
      login_2nd_step(totp.now, skip_2fa: 'yes')
      loggedin?.should == true

      logout

      login
      loggedin?.should == true
    end

    it "allows multiple users to skip 2FA on one browser" do
      set_devices_random_totp

      login('admin')
      totp = ROTP::TOTP.new(@secret)
      login_2nd_step(totp.now, skip_2fa: 'yes')
      loggedin?.should == true

      logout

      login('editor')
      totp = ROTP::TOTP.new(@secret)
      login_2nd_step(totp.at(Time.now + 30), skip_2fa: 'yes')
      loggedin?.should == true

      logout

      login('admin')
      loggedin?.should == true

      logout

      login('editor')
      loggedin?.should == true
    end
  end
end
