<?php
namespace LWare\Queue;
/*
    This file is part of LWare\Queue.

    LWare\Queue is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    LWare\Queue is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU Lesser Public License
    along with LWare\Queue.  If not, see <http://www.gnu.org/licenses/>.
 */


require_once __DIR__ . '/../lib/ApiProcess.php' ;
require_once __DIR__ . '/../lib/MessageQueue.php' ;
require_once __DIR__ . '/../lib/MessageDaemon.php';

class MessageDaemonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageDaemon
     */
    protected $object;

    protected $appid ;

    protected $xname;
    protected $qname;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new MessageDaemon;
        $this->appid = 'id_message_test';
        $this->xname = 'xchange_' . getmypid();
        $this->qname = 'qname_' . getmypid();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    

    /**
     * @covers \LWare\Queue\MessageDaemon::appId
     * @covers \LWare\Queue\MessageDaemon::isName
     * @covers \LWare\Queue\MessageDaemon::_addFunction
     */
    public function testAppIdAddOK()
    {
        $this->object->appId( 'test' , function (){} );
        $this->assertTrue( $this->object->isName('test' ) );
    }

    /**
     * @covers \LWare\Queue\MessageDaemon::removeAppId
     */
    public function testRemoveAppIdOK()
    {
        $this->object->appId( 'test' , function (){});
        $this->assertTrue( $this->object->removeAppId( 'test') );
        $this->assertFalse( $this->object->isName( 'test' ) );
    }
    /**
     * @covers \LWare\Queue\MessageDaemon::removeAppId
     */
    public function testRemoveDefaultOK()
    {
        $this->object->noMatches( 'test' , function (){});
        $this->assertTrue( $this->object->removeAppId( 'test') );
        $this->assertFalse( $this->object->isName( 'test' ) );
    }
    
    /**
     * @covers \LWare\Queue\MessageDaemon::removeAppId
     */
    public function testRemoveAppIdBad()
    {
        $this->assertFalse( $this->object->removeAppId( 'test') );
    }
    /**
     * @covers \LWare\Queue\MessageDaemon::getApiProcess
     */
    public function testGetApiProcess()
    {
        $function = function(){};
        $this->object->appId( 'test' , $function );
        $f2 = $this->object->getApiProcess( 'test' ) ;
        $this->assertTrue( $f2[0] instanceof ApiProcess );
    }
    /**
     * @covers \LWare\Queue\MessageDaemon::getApiProcess
     */
    public function testGetDefaultProcess()
    {
        $function = function(){};
        $this->object->noMatches( 'test' , $function );
        $f2 = $this->object->getApiProcess( 'test' ) ;
        $this->assertTrue( $f2[0] instanceof ApiProcess );
    }
    /**
     * @covers \LWare\Queue\MessageDaemon::getApiProcess
     */
    public function testGetApiProcessNull()
    {
        
        $f2 = $this->object->getApiProcess( 'test' ) ;
        $this->assertEquals( $f2[0] , NULL );
    }

    /**
     * @covers \LWare\Queue\MessageDaemon::match
     * @expectedException \LWare\Queue\MsgExceptionApi
     */
    public function testMatch()
    {
        $this->object->match('test', function(){} );
    }

    /**
     * @covers \LWare\Queue\MessageDaemon::always
     *@covers \LWare\Queue\MessageDaemon::_addFunction
     */
    public function testAlways()
    {
        $this->object->noMatches('test', function () {} );
        $this->assertTrue( $this->object->isName( 'test' ) );
        $type = $this->object->getApiProcess( 'test' );
        $type = $type[0]->getType();
        $this->assertEquals(
                 ApiProcess::API_ALL ,
                 $type  ,
                "$type not equal to " . ApiProcess::API_ALL
                );
    }

    /**
     * @covers \LWare\Queue\MessageDaemon::noMatches
     * @covers \LWare\Queue\MessageDaemon::_addFunction
     */
    public function testNoMatches()
    {
        $this->object->noMatches('test', function () {} );
        $this->assertTrue( $this->object->isName( 'test' ) );
        $type = $this->object->getApiProcess( 'test' );
        $type = $type[0]->getType();
        $this->assertEquals(
                 ApiProcess::API_ALL ,
                 $type  ,
                "$type not equal to " . ApiProcess::API_ALL
                );
    }

    /* ===================================================================
     *                     run()
     * ===================================================================
     */
       /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     */
    
    public function testRunDaemonOneTimeOK()
    {
        $flag = false;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env, MessageDaemon $d ) use ( &$flag )
                        {
                            $flag = true ;
                            $q->ack( $env->getDeliveryTag() );
                            $d->setProcessingFlag(MessageDaemon::HALT_DAEMON );
                        }
             );
        $qd->setAppId( $appid )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $this->assertTrue( $qd->run() );
        $this->assertTrue( $flag );
        $qd->getQueue()->delete();              // Destroy the queue
        $qd->getExchange('xtest')->delete();    // and destory the exchange
    }

           /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     */

    public function testRunDaemonMultipleTimesOK()
    {
        $flag = 0;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env, MessageDaemon $d ) use ( &$flag )
                        {
                            $flag++ ;
                            $q->ack( $env->getDeliveryTag() );
                            if( $flag > 1 )
                                $d->setProcessingFlag(MessageDaemon::HALT_DAEMON );
                        }
             );
        $qd->setAppId( $appid )
            ->publish( 'xtest' , 'message body1' , 'ktest' );
        $qd->publish(  'xtest' , 'message body2' , 'ktest' );
        $this->assertTrue( $qd->run() );
        $this->assertEquals( 2 , $flag );

        $qd->getQueue()->delete();              // Destroy the queue
        $qd->getExchange('xtest')->delete();    // and destory the exchange
    }
    
    /* ===================================================================
     *                     runOnce()
     * ===================================================================
     */
    /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     */
    public function testRunOnceOK()
    {
        $flag = false;

        $exParm = array( 
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') , 'declare'=>true) )
           ->appId( $appid ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env , MessageDaemon $d ) use ( &$flag )
                        {
                            $d->printDebug( "In routine for testRunOnceOK" );
                            $flag = true ;
                            $q->ack( $env->getDeliveryTag() );
                        }
             );
        $qd->setAppId( $appid )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce() ;
        $this->assertTrue( $flag );
    }
    
    /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     */
    public function testRunOnceMultipleOK()
    {
        $flag = 0;
        $second = 0;

        $appid = $this->appid . '_' . __LINE__ ;
        $exParm = array( 
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env, MessageDaemon $d ) use ( &$flag )
                        {
                            $d->printDebug( 'IN FIRST APP' );
                            $flag++ ;
                            $q->ack( $env->getDeliveryTag() );
                        }
             );
             
             
        $qd->appId( $appid ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env , MessageDaemon $d) use ( &$flag,&$second )
                        {
                            $d->printDebug( 'IN SECOND APP' );
                            $flag++ ;
                            $second++;
                        }
             );
        $apis = $qd->getApiProcess($appid);
        $this->assertEquals( 2 , count( $apis ) );
        $qd->setAppId( $appid )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce() ;
        $this->assertEquals( $flag , 2 );
        $this->assertEquals( $second , 1 );
    }

    /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     */
    public function testRunOnceWithBeforeOK()
    {
        $flag = false;
        $before = false;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$flag )
                        {
                            $flag = true ;
                            $q->ack( $env->getDeliveryTag() );
                        }
             )
            ->before( 
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$before )
                        {
                            $before = true ;
                        }
                        );
        $qd->setAppId( $appid )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce() ;
        $this->assertTrue( $flag );
        $this->assertTrue( $before );
    }

    /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     */
    public function testRunOnceWithMultipleBeforeOK()
    {
        $flag = false;
        $before = 0;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name' =>'qtest'
                               , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid  ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$flag )
                        {
                            $flag = true ;
                            $q->ack( $env->getDeliveryTag() );
                        }
             )
            ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$before )
                        {
                            $before++ ;
                        }
                        )
            ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$before )
                        {
                            $before++ ;
                        }
                        )
        ;
        $qd->setAppId( $appid  )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce();
        $this->assertTrue( $flag );
        $this->assertEquals( 2, $before );
    }

    /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     */
    public function testRunOnceWithMultipleAfterOK()
    {
        $flag = false;
        $after = 0;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid  ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$flag )
                        {
                            $flag = true ;
                            $q->ack( $env->getDeliveryTag() );
                        }
             )
            ->after(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$after )
                        {
                            $after++ ;
                        }
                   )
            ->after(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$after )
                        {
                            $after++ ;
                        }
                     )
        ;
        $qd->printDebug("TEST : " . __LINE__ );
        $qd->setAppId( $appid  )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce() ;
        $this->assertTrue( $flag );
        $this->assertEquals( 2, $after );
    }

    /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     */
    public function testRunOnceWithBeforeAfterOK()
    {
        $flag = false;
        $after = 0;
        $before = 0;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid  ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$flag )
                        {
                            $flag = true ;
                            $q->ack( $env->getDeliveryTag() );
                        }
             )
            ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$before )
                        {
                            $before++ ;
                        }
                   )
            ->after(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$after )
                        {
                            $after++ ;
                        }
                     )
        ;
        $qd->setAppId( $appid  )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce() ;
        $this->assertTrue( $flag );
        $this->assertEquals( 1, $before );
        $this->assertEquals( 1, $after );
    }

    /**
     * This will test running with one match and a 'norun' non-match.
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     * @covers \LWare\Queue\ApiProcess::before
     * @covers \LWare\Queue\ApiProcess::after
     * @covers \LWare\Queue\ApiProcess::testAndRun
     * @covers \LWare\Queue\ApiProcess::execute
     * @covers \LWare\Queue\ApiProcess::getArguments
     * @covers \LWare\Queue\ApiProcess::doGetArguments
     */
    public function testRunNoneWithBeforeAfterOK()
    {
        $flag = false;
        $goodrun = false;
        $after = 0;
        $before = 0;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid  ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$flag , &$goodrun)
                        {
                            $q->ack( $env->getDeliveryTag() );
                            $goodrun = true;
                        }
             );

          $qd->appId( 'norun' ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$flag )
                        {
                            $flag = true ;
                        }
             )
            ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$before )
                        {
                            $before++ ;
                        }
                   )
            ->after(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$after )
                        {
                            $after++ ;
                        }
                     )
        ;
        $qd->setAppId( $appid  )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce();
        $this->assertTrue( $goodrun );
        $this->assertFalse( $flag );
        $this->assertEquals( 0, $before );
        $this->assertEquals( 0, $after );
    }

    /**
     * This will test running with one match and a 'norun' non-match.
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     * @covers \LWare\Queue\ApiProcess::before
     * @covers \LWare\Queue\ApiProcess::after
     * @covers \LWare\Queue\ApiProcess::testAndRun
     * @covers \LWare\Queue\ApiProcess::execute
     * @covers \LWare\Queue\ApiProcess::getArguments
     * @covers \LWare\Queue\ApiProcess::doGetArguments
     */
    public function testRunOnceDefaultOnly()
    {
        $badrun = false;
        $goodrun = false;
        $after = 0;
        $before = 0;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( 'norunhere'  ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$badrun , &$goodrun)
                        {
                            $badrun = true;
                            
                        }
             );

           $qd->noMatches( 'nomatch' ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$goodrun )
                        {
                            $goodrun = true;
                            $q->ack( $env->getDeliveryTag() );
                        }
             )
            ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$before )
                        {
                            $before++ ;
                        }
                   )
            ->after(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$after )
                        {
                            $after++ ;
                        }
                     )
        ;
        $qd->setAppId( $appid  )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce();
        $this->assertTrue(  $goodrun );
        $this->assertFalse( $badrun );
        $this->assertEquals( 1, $before );
        $this->assertEquals( 1, $after );
    }
    
    /*
     * ====================================================
     * Test the HALT feature. You can:
     * HALT_BEFORE_LIST = Stop BEFORE list in match (process remaining)
     * HALT_AFTER_LIST  = Stop after list in match
     * HALT_THIS_MATCH  = Stop THIS match only

     * HALT_AFTER_MATCH = Complete this match then stop
     * HALT_NOW         = Stop  now
     * ====================================================
     */
    /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     * @covers \LWare\Queue\ApiProcess::before
     * @covers \LWare\Queue\ApiProcess::after
     * @covers \LWare\Queue\ApiProcess::testAndRun
     * @covers \LWare\Queue\ApiProcess::execute
     * @covers \LWare\Queue\ApiProcess::getArguments
     * @covers \LWare\Queue\ApiProcess::doGetArguments
     */
    public function testRunOnceHaltBeforeList()
    {
        $flag   = false;
        $after  = 0;
        $before = 0;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid  ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$flag )
                        {
                            $flag = true ;
                        }
             )
             ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env , \LWare\Queue\MessageDaemon $demon ) use ( &$before )
                        {
                            $demon->setProcessingFlag( MessageDaemon::HALT_BEFORE_LIST );
                            $q->ack( $env->getDeliveryTag() );
                            $before++ ;
                        }
                   )
            ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env , \LWare\Queue\MessageDaemon $demon ) use ( &$before )
                        {
                            $before++ ;
                        }
                   )
            ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env , \LWare\Queue\MessageDaemon $demon ) use ( &$before )
                        {
                            $before++ ;
                        }
                   )
            ->after(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$after )
                        {
                            $after++ ;
                        }
                     )
        ;
        $qd->setAppId( $appid  )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce() ;
        $this->assertTrue( $flag );
        $this->assertEquals( 1, $before );
        $this->assertEquals( 1, $after );
    }
    /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     * @covers \LWare\Queue\ApiProcess::before
     * @covers \LWare\Queue\ApiProcess::after
     * @covers \LWare\Queue\ApiProcess::testAndRun
     * @covers \LWare\Queue\ApiProcess::execute
     * @covers \LWare\Queue\ApiProcess::getArguments
     * @covers \LWare\Queue\ApiProcess::doGetArguments
     */
    public function testRunOnceHaltAfterList()
    {
        $flag   = false;
        $after  = 0;
        $before = 0;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid  ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$flag )
                        {
                            $flag = true ;
                        }
             )
             ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env , \LWare\Queue\MessageDaemon $demon ) use ( &$before )
                        {
                            $demon->printDebug( "(before) Bits are " . $demon->getProcessingFlag() );
                            $demon->setProcessingFlag( MessageDaemon::HALT_AFTER_LIST );
                            $demon->printDebug( "(before) Bits are " . $demon->getProcessingFlag() );
                            $q->ack( $env->getDeliveryTag() );
                            $before++ ;
                        }
                   )
            ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env , \LWare\Queue\MessageDaemon $demon ) use ( &$before )
                        {
                            $demon->printDebug( "(before) Bits are " . $demon->getProcessingFlag() );
                            $before++ ;
                        }
                   )
            ->after(
                    function(\AMQPQueue $q , \AMQPEnvelope $env , \LWare\Queue\MessageDaemon $demon ) use ( &$after )
                        {
                            $after++ ;
                        }
                   )
            ->after(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$after )
                        {
                            $after++ ;
                        }
                     )
        ;
        $qd->setAppId( $appid  )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce() ;
        $this->assertTrue( $flag );
        $this->assertEquals( 2, $before );
        $this->assertEquals( 0, $after );
    }
    /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     * @covers \LWare\Queue\MessageDaemon::always
     * @covers \LWare\Queue\ApiProcess::before
     * @covers \LWare\Queue\ApiProcess::after
     * @covers \LWare\Queue\ApiProcess::testAndRun
     * @covers \LWare\Queue\ApiProcess::execute
     * @covers \LWare\Queue\ApiProcess::getArguments
     * @covers \LWare\Queue\ApiProcess::doGetArguments
     */
    public function testRunOnceHaltThisMatch()
    {
        error_reporting(E_ALL );
        $flag   = false;
        $after  = 0;
        $before = 0;
        $always = 0;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid  ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env , \LWare\Queue\MessageDaemon $demon ) use ( &$flag )
                        {
                            $demon->printDebug( "(run appid) Bits are " . $demon->getProcessingFlag( true) );
                            $flag = true ;
                        }
             )
            ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env , \LWare\Queue\MessageDaemon $demon ) use ( &$before )
                        {
                            $demon->setProcessingFlag( MessageDaemon::HALT_THIS_MATCH );
                            $demon->printDebug( "(run before) Bits are " . $demon->getProcessingFlag( true) );
                            $q->ack( $env->getDeliveryTag() );
                            $before++ ;
                        }
                   )
            ->after(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$after )
                        {
                            $after++ ;
                        }
                     )
        ;
        $qd->always('always-run-this' , 
                function( \LWare\Queue\MessageDaemon $demon ) use (&$always )
                    {
                        $demon->printDebug( "(always-run-this) Bits are " . $demon->getProcessingFlag( true) );
                        $always++;
                    }
         );
        
        $qd->setAppId( $appid  )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce() ;
        $this->assertFalse( $flag );
        $this->assertEquals( 1 , $before );
        $this->assertEquals( 0 , $after );
        $this->assertEquals( 1 , $always , 'Always should have been run');
    }
    
    /**
     * @covers \LWare\Queue\MessageDaemon::runOnce
     * @covers \LWare\Queue\MessageDaemon::_processMessage
     * @covers \LWare\Queue\MessageDaemon::always
     * @covers \LWare\Queue\MessageDaemon::_addFunction
     * @covers \LWare\Queue\ApiProcess::before
     * @covers \LWare\Queue\ApiProcess::after
     * @covers \LWare\Queue\ApiProcess::testAndRun
     * @covers \LWare\Queue\ApiProcess::execute
     * @covers \LWare\Queue\ApiProcess::getArguments
     * @covers \LWare\Queue\ApiProcess::doGetArguments
     */
    public function testRunOnceHaltNow()
    {
        error_reporting(E_ALL );
        $flag   = false;
        $after  = 0;
        $before = 0;
        $always = 0;

        $exParm = array(
            'name' => 'xtest'  ,
            'type' => \AMQP_EX_TYPE_DIRECT
        );
        $appid = $this->appid . '_' . __LINE__ ;
        $qd = new MessageDaemon();
        $qd->setDebug( false )
           ->setConnectionString( 'amqp://127.0.0.1' )
           ->createExchange( $exParm )
           ->createQueue( array( 'name'=> 'qtest' , 'bind' => array( 'xtest' , 'ktest') ) )
           ->appId( $appid  ,
                        function(\AMQPQueue $q , \AMQPEnvelope $env , \LWare\Queue\MessageDaemon $demon ) use ( &$flag )
                        {
                            $demon->printDebug( "(run appid) Bits are " . $demon->getProcessingFlag( true) );
                            $flag = true ;
                        }
             )
            ->before(
                    function(\AMQPQueue $q , \AMQPEnvelope $env , \LWare\Queue\MessageDaemon $demon ) use ( &$before )
                        {
                            $demon->setProcessingFlag( MessageDaemon::HALT_NOW );
                            $demon->printDebug( "(run before) Bits are " . $demon->getProcessingFlag( true) );
                            $q->ack( $env->getDeliveryTag() );
                            $before++ ;
                        }
                   )
            ->after(
                    function(\AMQPQueue $q , \AMQPEnvelope $env ) use ( &$after )
                        {
                            $after++ ;
                        }
                     )
        ;
        $qd->always('always-run-this' , 
                function( \LWare\Queue\MessageDaemon $demon ) use (&$always )
                    {
                        $demon->printDebug( "(always-run-this) Bits are " . $demon->getProcessingFlag( true) );
                        $always++;
                    }
         );
        
        $qd->setAppId( $appid  )
            ->publish( 'xtest' , 'message body' , 'ktest' );
        $qd->runOnce() ;
        $this->assertFalse( $flag );
        $this->assertEquals( 1 , $before );
        $this->assertEquals( 0 , $after );
        $this->assertEquals( 0 , $always , 'Always should not have been run');
    }
}
