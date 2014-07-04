<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Form
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

// Call Zend_Form_Element_HashTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "Zend_Form_Element_HashTest::main");
}

require_once 'Zend/Form/Element/Hash.php';

/**
 * Test class for Zend_Form_Element_Hash
 *
 * @category   Zend
 * @package    Zend_Form
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Form
 */
class Zend_Form_Element_HashTest extends PHPUnit_Framework_TestCase
{
    const HASH_SALT = 'foo-unit-test';
    
    /**
     * Runs the test methods of this class.
     *
     * @return void
     */
    public static function main()
    {

        $suite  = new PHPUnit_Framework_TestSuite("Zend_Form_Element_HashTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        if (isset($this->hash)) {
            unset($this->hash);
        }

        $session = new Zend_Form_Element_HashTest_SessionContainer();

        $this->element = new Zend_Form_Element_Hash('foo', array(
                'session' => $session,
                'salt' => self::HASH_SALT
        ));
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown()
    {
    }

    public function testHashElementSubclassesXhtmlElement()
    {
        $this->assertTrue($this->element instanceof Zend_Form_Element_Xhtml);
    }

    public function testHashElementInstanceOfBaseElement()
    {
        $this->assertTrue($this->element instanceof Zend_Form_Element);
    }

    public function testSaltPopulatedByDefault()
    {
        $salt = $this->element->getSalt();
        $this->assertFalse(empty($salt));
    }

    public function testCanSetSalt()
    {
        $salt = $this->element->getSalt();
        $this->element->setSalt('foobar');
        $this->assertNotEquals($salt, $this->element->getSalt());
        $this->assertEquals('foobar', $this->element->getSalt());
    }
    
    public function testSessionHashNameCollision()
    {
        $session = $this->element->getSession();
        $sessionVar = $this->element->getSalt();
        
        $this->element->initCsrfToken();
        $this->element->initCsrfValidator();
        $hash = $this->element->getHash();

        $newElement = new Zend_Form_Element_Hash('foo', array(
                'session' => $session,
                'salt' => 'foobar'
        ));
        $newElement->initCsrfToken();
        $newElement->initCsrfValidator();
        
        $this->assertEquals($hash, $session->$sessionVar);
    }

    public function testSaltChangesHash()
    {
        $orgSaltHash = $this->element->getHash();
        $this->element->setSalt('foobar');
        $this->assertNotNull($this->element->getHash());
        $this->assertNotEquals($orgSaltHash, $this->element->getHash());
    }
    
    public function testNewElementSaltChangeWithValueValidation()
    {
        $this->element->initCsrfToken();
        $session = $this->element->getSession();
        $hash = $this->element->getHash();

        $newElement = new Zend_Form_Element_Hash('foo', array(
                'session' => $session,
                'salt' => 'foobar'
        ));
        $newElement->setSalt($this->element->getSalt());
        
        $this->assertTrue($newElement->isValid($hash));
    }

    public function testTimeoutPopulatedByDefault()
    {
        $ttl = $this->element->getTimeout();
        $this->assertFalse(empty($ttl));
        $this->assertTrue(is_int($ttl));
    }

    public function testCanSetTimeout()
    {
        $ttl = $this->element->getTimeout();
        $this->element->setTimeout(3600);
        $this->assertNotEquals($ttl, $this->element->getTimeout());
        $this->assertEquals(3600, $this->element->getTimeout());
    }

    public function testGetHashReturnsHashValue()
    {
        $hash = $this->element->getHash();
        $this->assertFalse(empty($hash));
        $this->assertTrue(is_string($hash));
        $this->hash = $hash;
    }

    public function testGetHashSetsElementValueToHash()
    {
        $this->testGetHashReturnsHashValue();
        $this->assertEquals($this->hash, $this->element->getValue());
    }

    public function testHashIsMd5()
    {
        $this->testGetHashReturnsHashValue();
        $this->assertEquals(32, strlen($this->hash));
        $this->assertRegexp('/^[a-f0-9]{32}$/', $this->hash);
    }

    public function testLabelIsNull()
    {
        $this->assertNull($this->element->getLabel());
    }

    public function testSessionNameContainsName()
    {
        $sessionName = $this->element->getSessionName();
        $this->assertContains($this->element->getName(), $sessionName);
    }

    public function getView()
    {
        require_once 'Zend/View.php';
        $view = new Zend_View();
        $view->addHelperPath(dirname(__FILE__) . '/../../../../library/Zend/View/Helper');
        return $view;
    }

    public function testValidatorTokenReceivesSessionHashWhenPresent()
    {
        $this->_checkZf2794();

        $session = $this->element->getSession();
        $sessionVar = self::HASH_SALT;
        $session->$sessionVar = $this->element->getHash();
        
        $element = new Zend_Form_Element_Hash('foo', array('session' => $session, 'salt' => self::HASH_SALT));
        $validator = $element->getValidator('Identical');
        $this->assertEquals($session->$sessionVar, $validator->getToken());
    }

    public function testRenderInitializesSessionHashToken()
    {
        $session = $this->element->getSession();
        $sessionVar = $this->element->getSalt();
        
        $this->assertNull($session->$sessionVar);
        $html = $this->element->render($this->getView());

        $this->assertEquals($this->element->getHash(), $session->$sessionVar);
        $this->assertEquals(1, $session->setExpirationHops);
        $this->assertEquals($this->element->getTimeout(), $session->setExpirationSeconds);
    }

    public function testHashTokenIsRendered()
    {
        $html = $this->element->render($this->getView());
        $this->assertContains($this->element->getHash(), $html);
    }

    public function testHiddenInputRenderedByDefault()
    {
        $html = $this->element->render($this->getView());
        $this->assertRegexp('/<input[^>]*?type="hidden"/', $html, $html);
    }

    /**
     * @group ZF-7404
     */
    public function testShouldRenderHashTokenIfRenderedThroughMagicCall()
    {
        $this->element->setView($this->getView());
        $html = $this->element->renderViewHelper();
        $this->assertContains($this->element->getHash(), $html, 'Html is: ' . $html);
    }

    /**
     * Used by test methods susceptible to ZF-2794, marks a test as incomplete
     *
     * @link   http://framework.zend.com/issues/browse/ZF-2794
     * @return void
     */
    protected function _checkZf2794()
    {
        if (strtolower(substr(PHP_OS, 0, 3)) == 'win' && version_compare(PHP_VERSION, '5.1.4', '=')) {
            $this->markTestIncomplete('Error occurs for PHP 5.1.4 on Windows');
        }
    }
}

class Zend_Form_Element_HashTest_SessionContainer
{
    protected $_data = array();

    public function & __get($name)
    {
        return $this->_data[$name];
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }
    
    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

    public function __call($method, $args)
    {
        switch ($method) {
            case 'setExpirationHops':
            case 'setExpirationSeconds':
                $this->$method = array_shift($args);
                break;
            default:
        }
    }
    
    public function __toString()
    {
        return var_export($this->_data, true);
    }
}

// Call Zend_Form_Element_HashTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Zend_Form_Element_HashTest::main") {
    Zend_Form_Element_HashTest::main();
}
