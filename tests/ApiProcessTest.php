<?php

namespace LWare\Queue;

require_once __DIR__ . '/../lib/LWare/Queue/ApiProcess.php';

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2012-12-30 at 08:33:36.
 */
class ApiProcessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApiProcess
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new ApiProcess( 'match', ApiProcess::API_MATCH , function(){});
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    public function testSetDebugTrue(  )
    {
        $this->object->setDebug( true );
        $this->assertTrue( $this->object->isDebug() );
    }
    
    public function testSetDebugFalse()
    {
        $this->object->setDebug( false );
        $this->assertFalse( $this->object->isDebug() );
    }
    
    /**
     * @covers \LWare\Queue\ApiProcess::getMatch
     */
    public function testGetMatch()
    {
        $this->assertEquals( 'match' , $this->object->getMatch() );
    }
    
    /**
     * @covers \LWare\Queue\ApiProcess::SetMatch
     */
    public function testSetMatch()
    {
        $this->object->setMatch( 'junk' );
        $this->assertEquals( 'junk' , $this->object->getMatch() );
    }
    
    /**
     * @covers \LWare\Queue\ApiProcess::setType
     * @expectedException LWare\Queue\MsgExceptionApi
     */
    public function testSetTypeBad()
    {
        $this->object->setType( 99 );
    }
    
    /**
     * @covers \LWare\Queue\ApiProcess::getType
     */
    public function testGetTypeMatch()
    {
        $this->assertEquals( ApiProcess::API_MATCH , $this->object->getType() );
    }
    /**
     * @covers \LWare\Queue\ApiProcess::getType
     * @covers \LWare\Queue\ApiProcess::setType
     */
    public function testGetTypeAll()
    {
        $this->object->setType( ApiProcess::API_ALL );
        $this->assertEquals( ApiProcess::API_ALL , $this->object->getType() );
    }
    /**
     * @covers \LWare\Queue\ApiProcess::getType
     * @covers \LWare\Queue\ApiProcess::setType
     */
    public function testGetTypeRegex()
    {
        $this->object->setType( ApiProcess::API_REGEX );
        $this->assertEquals( ApiProcess::API_REGEX , $this->object->getType() );
    }
    
    /**
     * @covers \LWare\Queue\ApiProcess::getParameter
     * @covers \LWare\Queue\ApiProcess::setParameter
     * @covers \LWare\Queue\ApiProcess::isParameter
     */
     
    public function testGetParameterHit()
    {
        $this->object->setParameter('NAME' , 'value' );
        $this->assertTrue( $this->object->isParameter('Name' ) );
        $this->assertEquals( 'value' , $this->object->getParameter('name' ) );
    }
    
    /**
     * @covers \LWare\Queue\ApiProcess::getParameter
     */
    public function testGetParameterDefault()
    {
        $this->assertEquals( 'default' , $this->object->getParameter('notset', 'default' ) );
    }
     /**
     * @covers \LWare\Queue\ApiProcess::isParameter
     */
    public function testIsParameterFalse()
    {
        $this->assertFalse( $this->object->isParameter('notset' ) );
    }
    
}
