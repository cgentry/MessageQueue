<?php

/**
 * Simple command line utility to send/receive messages
 *
 * This is a simple program that lets you access the message queue
 * functions in order to gain access to queues.
 *
 * @author    Charles Gentry <cg-lware@charlesgentry.com>
 * @category  Queue
 * @package   LWare
 * @copyright Copyright (c) 2012 CGentry
 * @version   1.0
 * @since     2012-Dec-14
 */

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
require_once __DIR__ . '/../lib/LWare/Queue/ApiProcess.php' ;
require_once __DIR__ . '/../lib/LWare/Queue/MessageQueue.php' ;

$msgQ    = new \LWare\Queue\MessageQueue();
$debug  = false;
$exname = '';
$route  = NULL;
$put    = NULL;
$text   = NULL;
$type   = \AMQP_EX_TYPE_DIRECT ;
$ack    = AMQP_AUTOACK;
$attributes = array();
/**
 * @var \AMQPExchange
 */
$ex     = NULL;

/*
 * Parse the command line options and save them off
 */

for( $i=1 ; $i< $argc ; $i++ )
{
    switch ( $argv[ $i ])
    {
    case '--app' :
    case '--appid':
        $attributes[ 'app_id' ] = $argv[ ++$i ];
        break;

        case '--host' :
            $msgQ->setConnectionString( $argv[ ++$i ] );
            break;

        case '--debug' :
        case '--verbose' :
        case '-v' :
        case '-d' :
            $debug = true;
            break;

        case '--exchange' :
        case '--x' :
            $exname = $argv[ ++$i ];
            break;

        case '--queue' :
        case '--q' :
            $qname = $argv[ ++$i ];
            break;

        case '--route' :
        case '--r':
            $route = $argv[ ++$i ];
            break;

        case '--send' :
        case '--push' :
        case '--put'  :
            $put = true;
            break;

        case '--receive' :
        case '--get'     :
        case '--pop'     :
            $put = false;
            break;

        case '--message' :
        case '-m' :
            $text = $argv[ ++$i ];
            break;
        
        case '--type' :
            $type = $argv[ ++$i ];
            break;
        
        case '--noack':
            $ack = 0;
            break;
        case '--ack' :
            $ack = AMQP_AUTOACK;
            break;
        
        case '--help':
        case '-h':
        case '-?':
            help();
            
           exit();

        default:
            break;
    }
}

if( ! is_bool( $put ) )
{
    die( "You must include either either --send or --receive\n" );
}

$ex_parms = array( 
    'name' => $exname , 
    'type'=> $type 
);

if( $put )
{
    if( NULL == $text )
    {
        $fh = fopen( 'php://stdin' , r );
        $text = fread( $fh , 2048 );
    }
    $msgQ->createExchange( $ex_parms );
    
    $ex = $msgQ->getExchange( $exname );
    if ( $debug )
    {
        print "Name: " . $ex->getName() . "\n";
        print "Host: " . $msgQ->getConnection()->getHost() . "\n";
        print "Channel is connected: " . ( $msgQ->getChannel()->isConnected() ? 'True' : 'False' ) . "\n";
        print "Connection is connected: " . ( $msgQ->getConnection()->isConnected() ? 'True' : 'False' ) . "\n";
        print "Application id is: " . ( isset( $attributes[ 'app_id' ] ) ? '(not set)' : $attributes[ 'app_id' ] ) . "\n";

        print "Publish text\n";
    }
    $rtn = $ex->publish( $text, $route , AMQP_NOPARAM , $attributes );
    if ( $debug )
    {
        print "\nDone. Published '$text' on '$exname'  and '$route'\n";
    }
    
}else{
    $rtn = $msgQ //->createExchange( $ex_parms )
                ->createQueue( array( 'name' => $qname , 'bind' => array( $exname , $route ) ) )
                ->getQueue(  )
                ->get( $ack );
    print_r( $rtn );
}

function help()
{
    print <<<HELP
Command line interface for AMQP processing:

clamp --host amqp://host --debug --exchange name --queue name --route key --appid value --message text --send --receive --type type

      --appid value     Set the application id in the header
      --host connection This is formatted as a URI amqp://username:passwword@host:port/vhost
                        Each part can be left out and defaults will be used.
      --debug           Turn debugging on. Default is off.
      --exchange name   Set the exchange name to 'name'
      --queue name      Set the queue name to 'name'
      --route key       Use 'key for routing
      --message text    When sending, send 'text'. If nothing is given, get the
                        text to send from stdin.
      --send            Send text to 'key' using exchange 'name'
      --receive         Receive text from the queue 'name' and the routing key 'key'

HELP;
    exit(0);
}
