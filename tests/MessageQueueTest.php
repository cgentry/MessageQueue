<?php
namespace LWare\Queue;

require_once __DIR__ . '/../lib/LWare/Queue/MessageQueue.php';

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2012-12-31 at 18:49:05.
 */
class MessageQueueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageQueue
     */
    protected $object;
    protected $cstring = 'amqp://127.0.0.1';
    protected $appid   = 'mqtest';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new MessageQueue;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::setConnectionString
     * @covers \LWare\Queue\MessageQueue::getConnectionString
     */
    public function testSetConnectionString()
    {
        $qd = new MessageQueue();
        $qd->setConnectionString( $this->cstring );
        $this->assertEquals( $this->cstring , $qd->getConnectionString() );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::setConnectionString
     * @covers \LWare\Queue\MessageQueue::getConnectionValues
     */
    public function testGetConnectionValues()
    {
        
        $port = __LINE__;
        $this->object->setConnectionString( 'amqp://user:password@127.0.0.1:' . $port . '/vhost' );
        $parts = $this->object->getConnectionValues();
        $this->assertEquals( 'user'     , $parts[ 'user' ] );
        $this->assertEquals( 'password' , $parts[ 'pass' ] );
        $this->assertEquals( '127.0.0.1', $parts[ 'host' ] );
        $this->assertEquals( $port      , $parts[ 'port' ] );
        $this->assertEquals( '/vhost'   , $parts[ 'path' ] );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::setConnectionString
     * @covers \LWare\Queue\MessageQueue::getConnectionValues
     */
    public function testGetConnectionValuesDefault()
    {
        
        $port = __LINE__;
        $this->object->setConnectionString( 'amqp://user:password@127.0.0.1:' . $port  );
        $parts = $this->object->getConnectionValues();
        $this->assertEquals( 'user'     , $parts[ 'user' ] );
        $this->assertEquals( 'password' , $parts[ 'pass' ] );
        $this->assertEquals( '127.0.0.1', $parts[ 'host' ] );
        $this->assertEquals( $port      , $parts[ 'port' ] );
        $this->assertEquals( ini_get('amqp.vhost' )  , $parts[ 'path' ] );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::setConnectionString
     * @covers \LWare\Queue\MessageQueue::getConnectionValues
     */
    public function testGetConnectionValuesAll()
    {
        
        $this->object->setConnectionString( 'amqp://' );
        $parts = $this->object->getConnectionValues();
        $this->assertEquals( ini_get('amqp.login' )    , $parts[ 'user' ] );
        $this->assertEquals( ini_get('amqp.password' ) , $parts[ 'pass' ] );
        $this->assertEquals( ini_get('amqp.host' ), $parts[ 'host' ] );
        $this->assertEquals( ini_get('amqp.port' )      , $parts[ 'port' ] );
        $this->assertEquals( ini_get('amqp.vhost' )  , $parts[ 'path' ] );
    }
    
    public function testDebug()
    {
        $this->object->setDebug( true );
        $this->assertTrue( $this->object->isDebug() );
        $this->object->printDebug( "This should print" );
        $this->object->setDebug( false );
        $this->assertFalse( $this->object->isDebug() );
        $this->object->printDebug( "This should NOT print" );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::isConnected
     */
    public function testIsConnectedFalse()
    {
        $this->assertFalse( $this->object->isConnected() );
    }
    /**
     * @covers \LWare\Queue\MessageQueue::isQueueConnected
     */
    public function testIsQueueConnectedFalse()
    {
        $this->assertFalse( $this->object->isQueueConnected() );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::getConnection
     */
    public function testGetConnection()
    {
        $this->object->setConnectionString('amqp://localhost' );
        $conn = $this->object->getConnection();
        $this->assertTrue( $conn instanceof \AMQPConnection );

    }
    /**
     * This will test auto-reconnect when the connection gets closed early
     * @covers \LWare\Queue\MessageQueue::getConnection
     */
    public function testGetConnectionClose()
    {
        $this->object->setConnectionString('amqp://localhost' );
        $conn = $this->object->getConnection();
        $conn->disconnect();
        $conn = $this->object->getConnection();
        $this->assertTrue( $conn instanceof \AMQPConnection );

    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::getChannel
     * @covers \LWare\Queue\MessageQueue::getConnection
     * @covers \LWare\Queue\MessageQueue::isConnected
     */
    public function testGetChannel()
    {
        $this->object->setConnectionString('amqp://localhost' );
        $chan = $this->object->getChannel();
        $this->assertTrue( $chan instanceof \AMQPChannel );
        $this->assertTrue( $this->object->isConnected() ,
                'Connection has not been made' );
        
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::getAppId
     * @covers \LWare\Queue\MessageQueue::setAppId 
     */
    public function testSetGetAppid()
    {
        $this->assertEquals( '' , $this->object->getAppid() );
        $app_id = __LINE__ ;
        $this->object->setAppId( $app_id );
        $this->assertEquals( $app_id , $this->object->getAppid() );
        $app_id = __LINE__ ;
        $this->object->setAppId( $app_id );
        $this->assertEquals( $app_id , $this->object->getAppid() );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::getProcessingFlag
     * @covers \LWare\Queue\MessageQueue::setProcessingFlag
     */
    public function testSetProcessingBits()
    {
        $this->object->setProcessingFlag( MessageQueue::HALT_NOW );
        $this->assertEquals( MessageQueue::HALT_NOW , $this->object->getProcessingFlag( ) );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::checkProcessingBits
     * @covers \LWare\Queue\MessageQueue::setProcessingFlag
     */
    public function testSetProcessingBitsDancingBits()
    {
        $this->object->setProcessingFlag( 0xff );
        $i=1;
        while( $i < 256 )
        {
            $this->assertTrue(  $this->object->checkProcessingBits( $i ) 
                    , sprintf( "Failure at bit pattern: '0x%x'" , $i) );
            $i = ( $i << 1 );
        }
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::clearProcessingBits
     * @covers \LWare\Queue\MessageQueue::setProcessingFlag
     */
    public function testClearProcessingBitsDancingBits()
    {
        $mask = 0xff;
        $this->object->setProcessingFlag( $mask );
        $i=1;
        while( $mask != 0  )
        {
            $this->assertEquals( $mask, $this->object->getProcessingFlag( ) 
                    , sprintf( "Failure at bit pattern: '0x%x'" , $i) );
            
            $mask = ( $mask & ( ~ $i ) );     
            $this->object->clearProcessingBits($i );
            $i = ( $i << 1 );
        }
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::createExchange
     * @expectedException LWare\Queue\MsgExceptionExchange
     */
    public function testCreateExchangeBad()
    {
        $exParm = array(
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $this->object
                  ->setConnectionString($this->cstring )
                  ->createExchange( $exParm );
    }
    /**
     * @covers \LWare\Queue\MessageQueue::createExchange
     * @covers \LWare\Queue\MessageQueue::getQueue
     * @covers \LWare\Queue\MessageQueue::createQueue
     * @covers \LWare\Queue\MessageQueue::callMethods
     */
    public function testGetQueue()
    {
        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $q = $this->object
                  ->setConnectionString($this->cstring )
                  ->createExchange( $exParm )
                  ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
                  ->getQueue( );
        $this->assertTrue( $q instanceof \AMQPQueue );
        $this->assertTrue( $this->object->isQueueConnected() );
    }
    /**
     * @covers \LWare\Queue\MessageQueue::createExchange
     * @covers \LWare\Queue\MessageQueue::getQueue
     * @covers \LWare\Queue\MessageQueue::createQueue
     * @covers \LWare\Queue\MessageQueue::callMethods
     */
    public function testGetQueueUnknownParameter()
    {
        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $q = $this->object
                  ->setConnectionString($this->cstring )
                  ->createExchange( $exParm )
                  ->createQueue( array( 'testest' => 'junk' , 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
                  ->getQueue( );
        $this->assertTrue( $q instanceof \AMQPQueue );
        $this->assertTrue( $this->object->isQueueConnected() );
    }
    /**
     * @covers \LWare\Queue\MessageQueue::createExchange
     * @covers \LWare\Queue\MessageQueue::getQueue
     * @covers \LWare\Queue\MessageQueue::createQueue
     * @covers \LWare\Queue\MessageQueue::callMethods
     */
    public function testGetQueueReconnect()
    {
        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $q = $this->object
                  ->setConnectionString($this->cstring )
                  ->createExchange( $exParm )
                  ->createQueue( array( 'testest' => 'junk' , 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
                  ->getQueue( );
        $this->assertTrue( $q instanceof \AMQPQueue );
        $this->assertTrue( $this->object->isQueueConnected() );
        $this->object->destroyQueue();
        $q = $this->object->getQueue();
        $this->assertTrue( $q instanceof \AMQPQueue );
        $this->assertTrue( $this->object->isQueueConnected() );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::createExchange
     * @covers \LWare\Queue\MessageQueue::getQueue
     * @covers \LWare\Queue\MessageQueue::callMethods
     * @covers \LWare\Queue\MsgExceptionQueue::__construct
     * @expectedException LWare\Queue\MsgExceptionQueue
     */
    public function testGetQueueNotSetup()
    {
        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $q = $this->object
                  ->setConnectionString($this->cstring )
                  ->createExchange( $exParm )
                  ->getQueue( );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::createExchange
     * @covers \LWare\Queue\MessageQueue::getQueue
     * @covers \LWare\Queue\MessageQueue::callMethods
     * @expectedException LWare\Queue\MsgExceptionQueue
     */
    public function testGetQueueNoName()
    {
        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $q = $this->object
                  ->setConnectionString($this->cstring )
                  ->createExchange( $exParm )
                  ->createQueue( array( 'bind' => array( 'xtest' , 'ktest') ) )
                  ->getQueue( );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::createExchange
     * @covers \LWare\Queue\MessageQueue::getExchange
     * @covers \LWare\Queue\MessageQueue::callMethods
     */
    public function testGetExchange()
    {
        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $q = $this->object
                  ->setConnectionString($this->cstring )
                  ->createExchange( $exParm )
                  ->getExchange( 'xtest');
        $this->assertTrue( $q instanceof \AMQPExchange );
        $this->assertTrue( $this->object->isConnected() );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::createExchange
     * @covers \LWare\Queue\MessageQueue::getExchange
     * @covers \LWare\Queue\MessageQueue::callMethods
     */
    public function testGetExchangeRecreate()
    {
        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $q = $this->object
                  ->setConnectionString($this->cstring )
                  ->createExchange( $exParm )
                  ->getConnection();
        $q->disconnect();
        $q = $this->object->getExchange( 'xtest' );
        $this->assertTrue( $q instanceof \AMQPExchange );
        $this->assertTrue( $this->object->isConnected() );
    }
    
    /**
     * @covers \LWare\Queue\MessageQueue::createExchange
     * @covers \LWare\Queue\MessageQueue::getExchange
     * @covers \LWare\Queue\MessageQueue::callMethods
     * @expectedException LWare\Queue\MsgExceptionExchange
     */
    public function testGetExchangeBadName()
    {
        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $q = $this->object
                  ->setConnectionString($this->cstring )
                  ->createExchange( $exParm )
                  ->getExchange( 'nothere');
        
    }
}
