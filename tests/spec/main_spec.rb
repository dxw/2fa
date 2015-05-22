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
    @mysql.query("INSERT INTO wp_options SET option_name='options_client_applications', option_value='2'")
    @mysql.query("INSERT INTO wp_options SET option_name='options_client_applications_0_client_id', option_value='123'")
    @mysql.query("INSERT INTO wp_options SET option_name='options_client_applications_0_client_secret', option_value='456'")
    @mysql.query("INSERT INTO wp_options SET option_name='options_client_applications_0_name', option_value='Test application 1'")
    @mysql.query("INSERT INTO wp_options SET option_name='options_client_applications_0_redirect_uri', option_value='http://abc/happy'")
    @mysql.query("INSERT INTO wp_options SET option_name='options_client_applications_1_client_id', option_value='456'")
    @mysql.query("INSERT INTO wp_options SET option_name='options_client_applications_1_client_secret', option_value='789'")
    @mysql.query("INSERT INTO wp_options SET option_name='options_client_applications_1_name', option_value='Test application 2'")
    @mysql.query("INSERT INTO wp_options SET option_name='options_client_applications_1_redirect_uri', option_value='http://def/happy'")
    @mysql.query("UPDATE wp_users SET display_name='C. lupus'")

    # Start WP
    @wp_proc = fork do
      exec 'php -d sendmail_path=/bin/false -S localhost:8910 -t wordpress/'
    end
    Process.detach(@wp_proc)
    sleep(5)

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
    response.response.code.should == '302'
    @cookies = extract_cookies(response)

    # Verify that we've stored the cookies correctly
    response = Http::get(
      '/wp-admin/', 
      headers: {'Cookie' => @cookies},
    )
    response.response.code.should == '200'
  end

  after :all do
    # Stop WP
    Process.kill('TERM', @wp_proc)
  end

  # Tests

  describe "sanity check" do
    it "works" do
      response = Http::get('/wp-login.php')
      response.response.code.should == '200'
    end
  end
end
