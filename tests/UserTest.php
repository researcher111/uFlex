<?php
/**
 * Created by PhpStorm.
 * User: Pablo
 * Date: 3/29/14
 * Time: 11:23 PM
 */

namespace tests;


use Ptejada\UFlex\User;

class UserTest extends \PHPUnit_Framework_TestCase {
    /** @var User  */
    public $user;

    public function setUp()
    {
        // Instantiate the global session variable
        $_SESSION = array();
        // Instantiate the global cookie variable
        $_COOKIE = array();

        $this->user = new User();
        $this->user->config->database->dsn = 'sqlite::memory:';

        $this->user->start();

        // Creates the table
        $this->user->table->runQuery("
            CREATE TABLE IF NOT EXISTS _table_ (
              `user_id` int(7),
              `username` varchar(15) NOT NULL,
              `password` varchar(40) ,
              `email` varchar(35) ,
              `activated` tinyint(1) NOT NULL DEFAULT '0',
              `confirmation` varchar(35) ,
              `reg_date` int(11) ,
              `last_login` int(11) NOT NULL DEFAULT '0',
              PRIMARY KEY (`user_id`)
            )
        ");

        //Create user
        $this->user->table->runQuery('
            INSERT INTO _table_(`user_id`, `username`, `password`, `email`, `activated`, `reg_date`)
            VALUES (1,"pablo","18609a032b2504973748587e8c428334","pablo@live.com",1,1361145707)
        ');
    }

    public function testDefaultInitialization()
    {
        $this->user->login();
        $this->assertFalse($this->user->isSigned());
    }

    public function testLoginFromSession()
    {
        $_SESSION['userData'] = array(
            'data' => array(
                'user_id' => 1,
            ),
            'update' => true,
            'signed' => true,
        );

        $this->user->login();

        $this->assertFalse($this->user->log->hasError());
        $this->assertTrue($this->user->isSigned());

        $this->assertGreaterThanOrEqual(5, count($this->user->session->data->getAll()));
        $this->assertNotEmpty($this->user->username);
        $this->assertNotEmpty($this->user->password);
        $this->assertNotEmpty($this->user->email);

        $this->assertTrue($this->user->isSigned());
    }

    public function testLoginFromCookies()
    {
        $_COOKIE['auto'] = '130118609a032b973748587e8c42833465498745';

        ob_start();
        $this->user->login();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertFalse($this->user->log->hasError());

        // Expect the autologin cookie to be set
        $this->assertNotEmpty($output);
        $this->assertEquals(0, strpos($output, '<script>'));

        $this->assertGreaterThanOrEqual(5, count($this->user->session->data->getAll()));
        $this->assertNotEmpty($this->user->username);
        $this->assertNotEmpty($this->user->password);
        $this->assertNotEmpty($this->user->email);

        $this->assertTrue($this->user->isSigned());
    }

    public function testLoginWithCredentials()
    {
        $this->user->login();

        $this->assertFalse($this->user->log->hasError());

        $this->user->login('pablo', 123);
        $this->assertTrue($this->user->log->hasError());

        $this->user->login('pablo', 1234);
        $this->assertFalse($this->user->log->hasError());
    }

    public function testFieldValidation()
    {
        $userInfo = $this->getUserInfo();

        $userInfo['username'] = md5(time());
        $success = $this->user->register($userInfo);
        $this->assertEquals($success, !$this->user->log->hasError());
        $this->assertFalse($success);

        $userInfo['username'] = 'user 1';
        $success = $this->user->register($userInfo);
        $this->assertEquals($success, !$this->user->log->hasError());
        $this->assertFalse($success);

        $userInfo['username'] = 'u1';
        $success = $this->user->register($userInfo);
        $this->assertEquals($success, !$this->user->log->hasError());
        $this->assertFalse($success);

        $this->user->addValidation('username', '2-15');
        $success = $this->user->register($userInfo);
        $this->assertEquals($success, !$this->user->log->hasError());
        $this->assertTrue($success);

        $this->assertEquals(array(), $this->user->log->getErrors());
    }

    public function testRegisterNewAccount()
    {
        $userInfo = $this->getUserInfo(2);

        $success = $this->user->register($userInfo);

        $this->assertEquals($success, !$this->user->log->hasError());
        $this->assertTrue($success);

        $this->assertFalse($this->user->isSigned());
        $this->user->login($userInfo['username'], $userInfo['password']);
        $this->assertTrue($this->user->isSigned());

        $this->assertNotEmpty($this->user->username);
        $this->assertNotEmpty($this->user->password);
        $this->assertNotEmpty($this->user->email);
    }

    protected function getUserInfo($id=0)
    {
        return array(
            'user_id' => $id ? $id : rand(),
            'username' => 'user' . rand(),
            'password' => substr(md5(rand()), 0, 7),
            'email'   => substr(md5(rand()), 0, 5) . '@' . substr(md5(rand()), 0, 8) . '.com',
        );
    }
    protected function tearDown()
    {
        $this->user = null;
    }


}
 