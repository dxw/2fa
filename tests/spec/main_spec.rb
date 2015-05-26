require 'httparty'
require 'nokogiri'
require 'mysql'

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
  response.headers.get_fields('Set-Cookie').map {|a| a.split(';')[0]}.join('; ')
end

RSpec.configure do |config|
  config.expect_with :rspec do |c|
    c.syntax = [:expect, :should]
  end
end

def set_override(value)
  @mysql.query("UPDATE wp_usermeta SET meta_value='"+value+"' WHERE user_id=1 AND meta_key='2fa_override'")
end

def set_devices(value)
  @mysql.query("UPDATE wp_usermeta SET meta_value='"+value+"' WHERE user_id=1 AND meta_key='2fa_devices'")
end

def login
  # Store the test cookie
  response = Http::get('/wp-login.php')
  response.response.code.should == '200'
  @cookies = extract_cookies(response)

  # Log in
  response = Http::post(
    '/wp-login.php',
    body: {
      log: 'admin',
      pwd: 'foobar',
    },
    headers: {'Cookie' => @cookies},
  )
  @cookies = extract_cookies(response)
end

def loggedin?
  # Verify that we're logged in
  response = Http::get(
    '/wp-admin/',
    headers: {'Cookie' => @cookies},
  )
  response.response.code == '200'
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
    %w[2fa.php lib vendor.phar views].each do |f|
      system("cp -R ../#{f} wordpress/wp-content/plugins/2fa/").should be_truthy
    end

    # Set up DB
    @mysql.query("DROP DATABASE IF EXISTS "+db)
    @mysql.query("CREATE DATABASE "+db)
    @mysql.query("USE "+db)
    system("rm -f wordpress/wp-config.php").should be_truthy
    system("echo 'define(\"OAUTH2_SERVER_TEST_NONCE_OVERRIDE\", \"sudo\");' | wp --path=wordpress/ core config --dbname="+db+" --dbuser="+user+" --dbpass="+password+" --dbhost="+host+" --extra-php").should be_truthy
    system("wp --path=wordpress/ core multisite-install --url=http://localhost:8910/ --title=Test --admin_user=admin --admin_email=tom@dxw.com --admin_password=foobar").should be_truthy
    system("wp --path=wordpress/ plugin activate 2fa").should be_truthy

    # Set basic options to be overwritten in tests
    @mysql.query("INSERT INTO wp_usermeta SET user_id=1, meta_key='2fa_override', meta_value='no'")
    @mysql.query("INSERT INTO wp_usermeta SET user_id=1, meta_key='2fa_devices', meta_value=''")

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

  # Tests

  describe "login process" do
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
          headers: {'Cookie' => @cookies},
        )
        response.response.code.should == '302'
        response.headers.get_fields('Location').should == ['http://localhost:8910/wp-admin/users.php?page=2fa&step=setup']
      end

      it "disallows login with devices set" do
        set_override('yes')
        set_devices('a:1:{i:0;a:3:{s:4:"mode";s:4:"totp";s:4:"name";s:6:"meowyy";s:6:"secret";s:16:"5AZOON3OUHEDGA3H";}}')

        login
        loggedin?.should == false
      end
    end
  end
end
