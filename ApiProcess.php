<?php

namespace LWare\Queue;
/**
 * A process is a wrapper that handles routing to a class
 *
 * ApiProcess will be filter and wrap your class and call it as required.
 * It allows reflection to figure out what parameters should be called
 *
 * @author    Charles Gentry <cg-lware@charlesgentry.com>
 * @category  Queue
 * @package   LWare
 * @copyright Copyright (c) 2012 CGentry.
 * @version   1.0
 * @since     2012-Dec-10
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

class ApiProcess
{
    const API_MATCH = 1;
    const API_REGEX = 2;
    const API_ALL   = 3;    // ALways execute

    protected $_match     = NULL;
    protected $_type      = NULL;
    protected $_function  = NULL;
    protected $_before    = array();
    protected $_after     = array();
    protected $_param     = array();
    protected $_objects   = array();

    protected $_debug     = false;
    /**
     *
     * @var \ApiProcess
     */
    protected $_master;

    /**
     * Construct the class
     * @param string $match Name or regex for match
     * @param integer $type Type match function
     * @param mixed $function Closure, function or object/method to call
     */
    public function __construct( $match, $type , $function )
    {
        $this->setMatch( $match );
        $this->setType( $type );
        $this->setFunction( $function );
        $this->_objects[ 'this' ] = $this ;
    }

    /**
     * Set the debug flag
     * @param bool $debug
     * @return ApiProcess
     */
    public function setDebug( $debug )
    {
        if( is_bool( $debug ) )
        {         
            $this->_debug = $debug;
        }
        $this->printDebug( "(setDebug) is " . ( $this->_debug ? 'ON' : 'OFF' ) );
        return $this;
    }

    /**
     * Return the debug flag
     * @return bool
     */
    public function isDebug()
    {
        return $this->_debug ;
    }

    /**
     * Print debugging messages
     * @param string Message to print
     * @return \LWare\Queue\MessageQueue
     */
    public function printDebug( $msg )
    {
        if( false !== $this->_debug )
        {
            print( 'DEBUG: ' . rtrim($msg ) . "\n");
        }
    }
    
    /**
     * Set the match string and type for the API service
     * @param string $match What to match against
     * @return ApiProcess
     */
    public function setMatch( $match )
    {
        $this->_match = $match ;
	return $this;
    }

    /**
     * Get the match string
     * @return String
     **/
    public function getMatch( )
    {
        return $this->_match ;
    }
    /**
     * Set the function pointer for this process
     * @param mixed Function object or function
     * @return \ApiProcess 
     */
    public function setFunction( $function )
    {
        $this->_function = $function ;
        return $this;
    }

    /**
     * Set the type of match to process
     * @param type $type
     * @return \ApiProcess
     * @throws \LWare\Queue\MsgExceptionApi
     */
    public function setType ( $type )
    {
        if( self::API_MATCH != $type
        &&  self::API_REGEX != $type
        &&  self::API_ALL   != $type )
        {
            require_once __DIR__ . '/MessageExceptions.php' ;
            throw new MsgExceptionApi( "Wrong Api type '$type'" );
        }
        $this->_type = $type ;
        return $this;
    }

    /**
     * return the type this function is
     * @return integer
     */
    public function getType()
    {
        return $this->_type ;
    }

    /*
     *  Parameters are a way of passing data around in parameters
     *  or by calling this particular class
     */
    /**
     * Save a named parameter
     * @param string $name
     * @param mixed $value
     * @return \ApiProcess
     */
    public function setParameter( $name , $value )
    {
        $this->_param[ strtolower( $name ) ] = $value;
        return $this;
    }

    /**
     * Fetch a named parameter from the store
     * @param string $name
     * @param mixed $default value
     * @return  mixed
     */
    public function getParameter( $name , $default=NULL )
    {
        $name = strtolower( $name );
        return ( isset( $this->_param[ $name ] )
               ? $this->_param[ $name ]
               : $default
            );
    }

    /**
     * Determine if a parameter is set
     * @param string $name
     * @return boolean
     */
    public function isParameter( $name )
    {
        $name = strtolower( $name );
        return isset( $this->_param[ $name ] );
    }




    /**
     * Save a function on the 'before' list. This gets fired before the main
     * @param mixed $function
     * @return \ApiProcess
     */
    public function before( $function )
    {
        $name = 'before.' . $this->_match . '.' . count( $this->_before);
        $this->_before[] = new ApiProcess( $name ,self::API_ALL, $function ) ;
        return $this;
    }

    /**
     * Save a funciton on the 'after' list. This gets fired after the main
     * @param type $function
     * @return \ApiProcess
     */
    public function after( $function )
    {
        $name = 'after.' . $this->_match . '.' . count( $this->_after);
        $this->_after[] = new ApiProcess( $name , self::API_ALL , $function ) ;
        return $this;
    }

    /**
     * The main processing routine. This will run if there is a match
     * @param \ApiProcess $proc
     * @param \AMQPEnvelope $env
     * @param \AMQPQueue $queue
     * @return boolean
     */
    public function testAndRun( 
            MessageQueue  $proc , 
            \AMQPEnvelope $env, 
            \AMQPQueue    $queue ,
            $clear = true )
    {
        // Setup default substitutions available
        $this->_objects['master'] = &$proc;
        $this->_objects['env']    = &$env ;
        $this->_objects['queue']  = &$queue ;
        $appId = $env->getAppId();
        $this->setParameter( 'apiId' , $appId );

        $this->printDebug( "(testAndRun): match: '" . $this->_match . "'");
        $this->printDebug( "(testAndRun): Type:  '" . $this->_type . "'" );
        $this->printDebug( "(testAndRun): API:   '$appId'" );

        switch ( $this->_type )
        {
            case self::API_ALL :            // ALWAYS run
                $this->printDebug(  "(testAndRun) RUN with ALL\n" );
                $this->execute( $clear );           // We ran it
                return true;                // Found one...
                break;

            case self::API_MATCH :          // On exact match
                if ( $appId == $this->_match )
                {
                    $this->printDebug( "(testAndRun) RUN with match\n" );
                    $this->execute( $clear );
                    return true;
                }
                break;

            case self::API_REGEX :          // NOT YET IMPLEMENTED
            default:
                break;
        }
        $this->printDebug(  "(testAndRun) NO RUN\n" );

        return false;
    }

    /**
     * Execute will perform the actual execution of the functions.
     * @returns \Lware\Queue\ApiProcess
     */
    protected function execute( $clear = true )
    {
        $master = $this->_objects[ 'master' ];
        if( $clear )
        {
            $master->clearProcessingBits( MessageQueue::HALT_THIS_MATCH );
        }
        /**
         * Run through the 'before' functions. These are 'subtasks' 
         */
        $this->printDebug( "(execute) BEFORE Bits are " . $master->getProcessingFlag(true) );
        foreach ( $this->_before as $before )
        {
            if ( $master->checkProcessingBits( MessageQueue::HALT_BEFORE_LIST ) )
                break;
            $before->testAndRun(
                    $this->_objects[ 'master' ],
                    $this->_objects[ 'env' ],
                    $this->_objects[ 'queue' ] ,
                    false
            );
        }
        /**
         * Main task... if they haven't halted it, then run the funcion
         * with the arguments required. (Arguments are done via reflection)
         */
        $this->printDebug( "(execute) MAIN   Bits are " . $master->getProcessingFlag(true) );
 
        if ( !$master->checkProcessingBits( MessageQueue::HALT_MAIN_APPID ))
        {

            $args = $this->getArguments();
            call_user_func_array( $this->_function, $args );
            
        }

        /**
         * Post processing. If they haven't stopped everything, run all the
         * after lists.
         */

        $this->printDebug( "(execute) AFTER  Bits are " . $master->getProcessingFlag(true) );
        foreach ($this->_after as $after)
        {
            if ($master->checkProcessingBits( MessageQueue::HALT_AFTER_LIST ))
                break;
            $after->testAndRun(
                    $this->_objects['master'], 
                    $this->_objects['env'], 
                    $this->_objects['queue'] ,
                    false
            );
        }

        return $this;
    }

    /**
     * Returns the arguments to pass to the controller.
     * @throws \RuntimeException When value for argument given is not provided
     *
     * @api
     */
    public function getArguments(  )
    {
        if (is_array( $this->_function )) {
            $r = new \ReflectionMethod($this->_function[0], $this->_function[1]);

        } elseif (is_object($this->_function) && !$this->_function instanceof \Closure) {
            $r = new \ReflectionObject($this->_function);
            $r = $r->getMethod('__invoke');

        } else {
            $r = new \ReflectionFunction($this->_function);
        }

        return $this->doGetArguments(  $r->getParameters() );
    }

    /**
     * This attempts to resolve the reflective parameter list
     * @param array $parameter list from reflection
     * @return array of values
     * @throws \LWare\Queue\MsgExceptionApi
     */
    protected function doGetArguments( array $parameters )
    {
        $arguments = array( );

        foreach ( $parameters as $param )                   // LEVEL 1
        {
            $class = $param->getClass();
            if ( $this->isParameter( (string)  $param->name ) )
            {   // Param is in array
                $arguments[ ] = $this->getParameter( (string) $param->name );
            } elseif ( $class )
            {
                foreach ( $this->_objects as $object )      // LEVEL 2
                {
                    if ( $class->isInstance( $object ) )
                    {
                        $arguments[ ] = $object;
                        continue 2;                        // SKIP TO LEVEL 1
                    }
                }
            }

            if ( $param->isDefaultValueAvailable() )
            {
                $arguments[ ] = $param->getDefaultValue();
            } else
            {
                if ( is_array( $this->_function ) )
                {
                    $repr = sprintf( '%s::%s()', get_class( $this->_function[ 0 ] ), $this->_function[ 1 ] );
                } elseif ( is_object( $this->_function ) )
                {
                    $repr = get_class( $this->_function );
                } else
                {
                    $repr = $this->_function;
                }

                throw new MsgExceptionApi( sprintf( 'Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->name ) );
            }
        }

        return $arguments;
    }
}

