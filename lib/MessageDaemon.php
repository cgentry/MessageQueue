<?php
namespace LWare\Queue;
/**
 *  This provides routing services similar to Silex framework
 *
 *  This simplifieds linking functions/classes to message queues
 *
 * @author    Charles Gentry <cg-lware@charlesgentry.com>
 * @category  Queue
 * @package   LWare
 * @copyright Copyright (c) CGentry 2012
 * @version   1.0
 * @since     2012-Dec-11
 *
 * This file is part of LWare\Queue.
 *
 * LWare\Queue is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LWare\Queue is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser Public License
 * along with LWare\Queue.  If not, see <http://www.gnu.org/licenses/>.
 */

class MessageDaemon extends MessageQueue {

    /**
     * Hold the API functions that will be called
     * @var array
     */
    protected $_api     = array();      // Match/Always run routines
    /**
     * Hold the default routines to call when no matches occur in _api.
     * @var array
     */
    protected $_default = array();      // Nothing ran...run these


    /**
     * Determine if a name is used as either an API or a DEFAULT process
     * @param string $name
     * @return boolean
     */
    public function isName( $name )
    {
        return array_key_exists( $name , $this->_api )
           ||  array_key_exists( $name , $this->_default );
    }
    /**
     * Remove the  function that was previously added.
     * @param string $name
     * @return bool True if removed, false if not
     */
    public function removeAppId( $name )
    {
        if ( array_key_exists( $name, $this->_api ) )
        {
            $this->printDebug( "removeAppId api - $name" );
            unset( $this->_api[ $name ] );
            return true;
        }
        if ( array_key_exists( $name, $this->_default ) )
        {
            $this->printDebug( "removeAppId default - $name" );
            unset( $this->_default[ $name ] );
            return true;
        }

        return false;
    }
    /**
     * Add in an APP ID matching function. This matches exactly
     * @param string  name to attach to the function
     * @param type $function to execute
     * @return ApiProcess  - This allows you to chain processing options
     */
    public function appId( $name , $function  , $tag = NULL)
    {
        return $this->_addFunction( 
                $this->_api , 
                ApiProcess::API_MATCH , 
                $name, 
                $function, 
                $tag );
    }

    /**
     * Return the ApiProcess object for a given name
     * @param string Name
     * @return mixed \LWare\Queue\ApiProcess if found null if not
     */
    public function getApiProcess( $name )
    {
        if( array_key_exists( $name , $this->_api ) )
        {
            return $this->_api[ $name ];
        }
        if( array_key_exists( $name , $this->_default ) )
        {
            return $this->_default[ $name ];
        }
        return null;
    }

    private function _addFunction( &$where, $type , $name, $function, $tag )
    {
        $tag = ( null === $tag ? $name : $tag );

        $api = new ApiProcess( $name, $type , $function );
        if( !array_key_exists($tag, $where))
        {
             $where[ $tag ] = array();
        }
        $where[ $tag ][] = $api;
        return $api;
    }
    /**
     * @todo Implement. This is a skeleton routine
     * @param string  name to attach to the function
     * @param type $function to execute
     * @return \LWare\Queue\ApiProcess
     */
    public function match( $match , $function )
    {
        require_once __DIR__ . '/MessageExceptions.php' ;
        throw new MsgExceptionApi( 'Match is not yet implemented' );     
    }

    /**
     * This will register a routine to always be called.
     * @param string  name to attach to the function
     * @param type $function to execute
     * @return \LWare\Queue\ApiProcess
     */
    public function always( $name ,  $function , $tag=NULL)
    {
        return $this->_addFunction( 
                $this->_api , 
                ApiProcess::API_ALL , 
                $name, 
                $function, 
                $tag );
    }

    /**
     * Add in a 'nothing found' list entry. If there is nothing found we execute
     * all the nomatches
     * @param string  name to attach to the function
     * @param type $function to execute
     * @return \LWare\Queue\ApiProcess
     */
    public function noMatches( $name , $function , $tag = NULL )
    {
        return $this->_addFunction( 
                $this->_default , 
                ApiProcess::API_ALL , 
                $name, 
                $function, 
                $tag );
    }

    /**
     * Process all the messages that come in in a loop. To cancel, the routine must return a HALT_DAEMON
     * @param integer $options
     * @return bool  True if we ended the run, false if we terminated on error
     */
    public function run( $options = \AMQP_NOPARAM )
    {
        $counter = 0;
        while ( $counter < 3 )
        {
            try
            {
                $this->getQueue()
                      ->consume( array( $this, '_processMessage' ), $options );
                return true;

            } catch ( \AMQPChannelException  $e )
            {
                $counter++;
            }catch ( \AMQPConnectionException  $e )
            {
                $counter++;
            }
        }
        error_log( "Exception occured and too many retries: " . $e->getMessage() );
        return false;
    }

    /**
     * Run and process only one message at a time.
     * @param int Either null or \AMQP_AUTOACK
     * @return bool false if didn't run otherwise return from processMessage
     */
    public function runOnce( $flag = null )
    {
        $this->printDebug( "(runOnce) : start" );
        $counter = 0;
        while ( $counter < 3 )
        {
            try
            {
                $q = $this->getQueue();
                $msg = ( null !== $flag ? $q->get( $flag ) : $q->get() );
                if( $this->isDebug() )
                {
                    $otxt = ( $msg instanceof \AMQPEnvelope ? $msg->getAppId() : '(no message)' );
                    $this->printDebug( "(runOnce) : " . $otxt);
                }
                if ( false !== $msg )
                {
                    return $this->_processMessage( $msg, $q );
                }
                return false ;
            } catch ( \AMQPChannelException  $e )
            {
                $counter++;
            }catch ( \AMQPConnectionException  $e )
            {
                $counter++;
            }
        }
        error_log( "Exception occured and too many retries: " . $e->getMessage() );
        return false;
    }

    /**
     * Attempt to run all the API Processes that meet the message criteria
     * @param \AMQPEnvelope $env
     * @param \AMQPQueue $queue
     */
    public function _processMessage( $env, $queue )
    {
        $found = false;

        /**
         *  Call each registered process. They will do the matching for us
         *  Each process can cancel the processing for the match or for all.
         */
        foreach ($this->_api as $key => $api_entries)
        {
            $this->printDebug("(process) check '$key'");
            $entry = 0;
            foreach ($api_entries as $api_process)
            {
                $entry++;
                $this->setProcessingFlag(self::CONTINUE_PROCESSING);
                $this->printDebug("(process) entry $entry" );
                $found = ( $api_process->setDebug($this->isDebug())
                                       ->testAndRun($this, $env, $queue, true)
                        || $found )
                ;
                if ($this->checkProcessingBits(self::HALT_AFTER_MATCH))
                {
                    $this->printDebug('(processMessage) Halt after match: ' .
                            $this->getProcessingFlag(true)
                    );
                    break 2;
                }
            }
        }

        // If we found NOTHING that matches, run the default routines
        if ( ! $found
        &&   ! $this->checkProcessingBits( self::HALT_NOW )
        &&    count( $this->_default ) > 0 )
        {
            $this->setProcessingFlag( self::CONTINUE_PROCESSING );
            foreach ( $this->_default as $key => $api_entries )
            {
                $this->printDebug("(process) Default '$key'");
                $entry = 0;
                foreach( $api_entries as $api_process )
                {
                    $entry++;
                    $found = true;
                    $this->printDebug("(process) Default '$entry;" );         
                    $api_process->setDebug( $this->isDebug() )
                                ->testAndRun( $this, $env, $queue , true);
                    if ( $this->checkProcessingBits( self::HALT_NOW) )    // Signaled a halt? Stop NOW
                        break 2;
                }
            }
        }

        /*
         * We return true if HALT_DAEMON is not set or false if HALT_DAEMON
         * is set. This will cause the consume process to terminate
         */
        return ( ! $this->checkProcessingBits( self::HALT_DAEMON ) );
    }
}
