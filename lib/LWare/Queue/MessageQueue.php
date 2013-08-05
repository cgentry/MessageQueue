<?php
namespace LWare\Queue;
/**
 *  This provides all the basic operations for AMQP with retries
 *
 *  Using this will allow you to have simpler queues that handle
 *  the error conditions and do retrying.
 *
 * @author    Charles Gentry <cg-lware@charlesgentry.com>
 * @category  Queue
 * @package   LWare
 * @copyright Copyright (c) 2012 
 * @version   1.0.1
 * @since     2012-Dec-11
 */


class MessageQueue
{
    protected $_exchange   = NULL;
    protected $_connection = NULL;
    protected $_connectionString = 'amqp://localhost';
    protected $_appID      = NULL;
    /**
     *
     * @var \AmpqChannel
     */
    protected $_channel    = NULL;
    /**
     *
     * @var \AmpqQueue
     */
    protected $_queue      = NULL;

    /**
     * Determine if we should continue to process
     * @var integer
     */
    protected $_continue_processing ;

    /**
     * @var bool
     */
    protected $_debug = false;

    const CONNECTION_HOST    = 'host';          // discrete connection values
    const CONNECTION_PORT    = 'port';
    const CONNECTION_VHOST   = 'vhost';
    const CONNECTION_LOGIN   = 'login';
    const CONNECTION_PWD     = 'password';

    const CONTINUE_PROCESSING = 0;
                                            // THESE BITS ARE TESTED IN API
    const HALT_BEFORE_LIST = 0x0001;
    const HALT_AFTER_LIST  = 0x0002;        // Stop after list in match
    const HALT_MAIN_APPID  = 0x0004;
    const HALT_THIS_MATCH  = 0x0007;        // Stop THIS match only

                                            // THESE BITS ARE TESTED IN DAEMON
    const HALT_AFTER_MATCH = 0x0008;        // Stop after this match
    const HALT_NOW         = 0x00FF;        // Stop after now

    const HALT_DAEMON      = 0x0100;        // Halt the daemon processing
    const HALT_DEMON       = 0x0100;        // Alternate spelling
    
    const OBJECT           = 'o';
    const CREATE           = 'c';


    /**
     * Get the bit-mapped processing flag
     * @return integer
     */
    public function getProcessingFlag($as_string = false)
    {
        if( ! $as_string )
        {
            return $this->_continue_processing ;
        }
        $values = array();
        if( $this->checkProcessingBits( self::HALT_NOW ) )
            $values[] = 'HALT_NOW' ;
        if( $this->checkProcessingBits( self::HALT_AFTER_MATCH ))
            $values[] = 'HALT_AFTER_MATCH';
        if( $this->checkProcessingBits( self::HALT_MAIN_APPID ) )
            $values[] = 'HALT_MAIN_APPID' ;
        if( $this->checkProcessingBits( self::HALT_BEFORE_LIST ) )
        {
            $values[] = 'HALT_BEFORE_LIST';
        }
        if( $this->checkProcessingBits( self::HALT_AFTER_LIST ) )
        {
            $values[] = 'HALT_AFTER_LIST';
        }
        $val = join( ' | ' , $values );
        return ( $val ? $val : 'CONTINUE' );
    }
    /**
     * Set the bit-mapped processing flag
     * @param int flag
     * @return \LWare\Queue\MessageQueue
     */
    public function setProcessingFlag( $flag = self::CONTINUE_PROCESSING )
    {
        $this->_continue_processing = $flag;
        $this->printDebug("(setProcessingFlag) Set flag to: " 
                . $this->getProcessingFlag( true ) );
        return $this;
    }

    /**
     * Check to see if specific bits are set in the processing flag
     * @param int bit-mapped processing flag
     * @return bool True if the bits are set
     */
    public function checkProcessingBits( $flag = self::CONTINUE_PROCESSING )
    {
        return ( ($this->_continue_processing & $flag) == $flag ) ;
    }

    /**
     * Clear specific bits in the processing flag
     * @param integer bits to be clear
     * @return \LWare\Queue\MessageQueue
     */
    public function clearProcessingBits( $flags = 0 )
    {
        $this->_continue_processing = $this->_continue_processing & ( ~ $flags );
        return $this;
    }

    /**
     * Set the application identifier (stored in the header's app_id filed)
     * @param string $name
     * @return \LWare\Queue\MessageQueue
     */
    public function setAppId( $name )
    {
        $this->_appID = $name ;
        return $this;
    }

    /**
     * return the application identifier
     * @return string
     */
    public function getAppId( )
    {
        return $this->_appID ;
    }


    /**
     * Set debugging
     * @param bool $flag
     * @return \LWare\Queue\MessageQueue
     */
    public function setDebug( $flag )
    {
        if( is_bool( $flag ) )
        {
            $this->_debug = $flag ;
        }
        return $this;
    }

    /**
     * Determine if debugging is on or off
     * @return bool
     */
    public function isDebug( )
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
            print 'DEBUG: ' . rtrim( $msg ) . "\n";
        }
    }

    /**
     * Determine if a connection is open or not
     * This does not test for queues or exchanges
     * @return bool
     */
    public function isConnected()
    {
        return ( NULL !== $this->_channel
              && NULL !== $this->_connection
              && $this->_channel->isConnected()
              && $this->_connection->isConnected()
               );
    }

    /**
     * Determine if the queue is connected or not. (Does not check exchanges)
     * @return bool True if connected false if not
     */
    public function isQueueConnected()
    {
        return ( $this->isConnected() 
              && null !== $this->_queue 
              && null !== $this->_queue[ self::OBJECT ]);
    }
    /**
     * Set a URI string for connection to the rabbitMQ
     * @param string $cstring
     * @return \LWare\Queue\MessageDaemon
     */
    public function setConnectionString( $cstring )
    {
        $this->_connectionString = $cstring ;
        return $this;
    }

    /**
     * Return the URI used to connect to RabbitMQ
     * @return string
     */
    public function getConnectionString( )
    {
        return $this->_connectionString ;
    }


    /**
     * Get the AMQP connection object or open it if it isn't opened yet.
     * @return \AMQPConnection
     */
    public function getConnection()
    {
        if ( NULL === $this->_connection
        ||   ! $this->_connection 
        ||   ! $this->_connection->isConnected() )
        {
            // See if we have a connection string...
            $parts = $this->getConnectionValues();

            // Create host and configure. Use standard parse_url return labels
            $this->_connection = new \AMQPConnection( array(
                    // AMQP        => URL Parser
                        'host'     => $parts[ 'host' ],
                        'port'     => $parts[ 'port' ],
                        'login'    => $parts[ 'user' ],
                        'password' => $parts[ 'pass' ],
                        'vhost'    => $parts[ 'path' ],
                    ) );
            $this->_connection->connect();
        }
        return $this->_connection;
    }

    /**
     * Return a channel or attempt to connet to a channel if none are available
     * @return \AMQPChannel
     */
    public function getChannel()
    {
        if( ! $this->isConnected() )
        {
            $this->_channel = new \AMQPChannel( $this->getConnection() );
        }
        return $this->_channel ;
    }

    /**
     * Get a named exchange that have previously been opened
     * @param string name of exchange to open
     * @return \AMQPExchange
     * @throws \LWare\Queue\MsgExceptionExchange
     */
    public function getExchange( $name = '' )
    {
        if( ! isset( $this->_exchange[ $name ] ) )
        {
            require_once __DIR__ . '/MessageExceptions.php' ;
            throw new MsgExceptionExchange( "No exchange named '$name' exists" );
        }
        /*
         * Find out if we are connected still (or ever)
        */
        if( !   $this->isConnected()
        ||  ! ( $this->_exchange[ $name][ self::OBJECT ] instanceof \AMQPExchange ))
        {
            $this->_channel = $this->_connection = NULL ;
            $this->createExchange( $this->_exchange[ $name ][ self::CREATE ] );
        }
        return $this->_exchange[ $name ][ self::OBJECT ];
    }

    /**
     * Create a new, named exchange
     * @param array $parms
     * @return \LWare\Queue\MessageDaemon
     * @throws \LWare\Queue\MsgExceptionExchange
     */
    public function createExchange( array $parms )
    {
        static $optionOrder = array( 
                    'name'     => true ,                    
                    'flags'    => true ,
                    'type'     => true ,
                    'argument' => true ,
                    'arguments'=> true ,
                    'declare'  => false,
                    'declareExchange'  => false,

                );
        
        $parms = array_change_key_case($parms, CASE_LOWER );
        
        if( ! isset( $parms[ 'name' ] ) )
        {
            require_once __DIR__ . '/MessageExceptions.php' ;
            throw new MsgExceptionExchange( 'No name for exchange' );
        }
        
        $name = $parms[ 'name' ];
        $ex   =  new \AMQPExchange( $this->getChannel() );
        $newVersion = method_exists($ex, 'declareExchange');
        $nameToUse  = ( $newVersion ? 'declareExchange' : 'declare' );

        if( $newVersion && isset( $parms['declare'])){
            if( ! isset( $parms[$nameToUse])){
                $parms[$nameToUse]=$parms['declare'];
            }
            unset( $parms['declare']);
        }
        
        if( ! isset( $parms[ $nameToUse ]))
        {
            $parms[ $nameToUse ] = '';
        }
        
        $this->callMethods( $ex , $parms , $optionOrder);

        $this->_exchange[ $name ] = array(
                        self::OBJECT    => $ex,
                        self::CREATE  => $parms );
        return $this ;
    }

    /**
     * Helper routine that will send a message out and requires an app_id
     * @param string $name for Exchange
     * @param mixed $message to send
     * @param string $routekey Routing key to use for sending
     * @param integer $flags Bit mapped flags
     * @param array $attributes Attributes to send
     * @return bool
     */
    public function publish(
            $name ,
            $message ,
            $routekey ,
            $flags = \AMQP_NOPARAM ,
            $attributes = array())
    {
        if( !array_key_exists( 'app_id', $attributes ))
        {
            if( null == $this->_appID )
            {
                throw new MsgExceptionQueue( "No app_id set in headers" );
            }
            $attributes[ 'app_id' ] = $this->_appID;
        }
        if( ! array_key_exists( 'timestamp' , $attributes ) )
        {
            $attributes[ 'timestamp' ] = time();
        }
        return $this->getExchange( $name )
                ->publish( $message , $routekey , $flags , $attributes );
    }


    /**
     * Get the queue objected for the opened queue
     * @return \AMQPQueue
     */
    public function getQueue()
    {
        if( ! $this->isQueueConnected() )
        {
            if( ! isset( $this->_queue[ self::CREATE ] ) )
            {
                require_once __DIR__ . '/MessageExceptions.php' ;
                throw new MsgExceptionQueue( "Queue has not been setup" );
            }
            $this->createQueue( $this->_queue[ self::CREATE ] );
        }
        return $this->_queue[ self::OBJECT ];
    }

    /**
     * Create a queue
     * @param array $parms
     * @throws \LWare\Queue\MsgExceptionExchange
     */
    public function createQueue( array $parms )
    {
        /**
         * These are the keys and order that are used for queue options
         */
        static $optionOrder = array( 
                    'name'         => true , 
                    'flags'        => true ,
                    'declare'      => false ,
		    'declareQueue' => false,
                    'argument'     => true,
                    'arguments'    => true,
                    'bind'         => true ,
                    'unbind'       => true ,
                    'testest'      => false, /*Doesn't exist...just for testing*/
                );
        
        $parms = array_change_key_case($parms, CASE_LOWER );
        
        if( ! isset( $parms[ 'name' ] ) )
        {
            require_once __DIR__ . '/MessageExceptions.php' ;
            throw new MsgExceptionQueue( 'No name for queue' );
        }
        
        $q = new \AMQPQueue( $this->getChannel() );
        
        // Library 1.0.10 and 1.2 compatibility
	$newVersion = method_exists($q, 'declareQueue');
        $nameToUse  = ( $newVersion ? 'declareQueue' : 'declare' );

        if( $newVersion && isset( $parms['declare'])){
            if( ! isset( $parms[$nameToUse])){
                $parms[$nameToUse]=$parms['declare'];
            }
            unset( $parms['declare']);
        }
        
        if( ! isset( $parms[ $nameToUse ]))
        {
            $parms[ $nameToUse ] = '';
        }
        
        
        
        $this->callMethods( $q , $parms , $optionOrder );
        
        $this->_queue = array(
                        self::OBJECT  => $q,
                        self::CREATE  => $parms );
        
        return $this;
    }
    
    /**
     * Close the queue by destroying the object. This is not a DELETE of the queue
     * @return \LWare\Queue\MessageQueue
     */
    public function destroyQueue()
    {
        $this->_queue[ self::OBJECT ] = null;
        return $this;
    }


    /**
     * Figure out what the connection values are. Use exceptions where required
     * @staticvar array $url_parts
     * @return array of connection values to use
     */
    public function getConnectionValues( )
    {
        static $url_parts = array(
            'host'  =>  'amqp.host'    ,
            'port'  =>  'amqp.port'    ,
            'user'  =>  'amqp.login'   ,
            'pass'  =>  'amqp.password',
            'path'  =>  'amqp.vhost'   ,
            );

        // if we have a connection string, start with that...
        $parts = parse_url($this->_connectionString );

        foreach( $url_parts as $part => $ini_part )
        {
            if( ! isset( $parts[ $part ] ) || '' == $parts[ $part ] )
            {
                $parts[ $part ] = ini_get( $ini_part );
            }
        }

       return $parts ;
    }

    /**
     * This will attempt to call all the methods that for a parameter array
     * @param mixed Object that we make the calls against
     * @param array method/value
     * @throws MsgExceptionQueue
     */
    protected function callMethods(&$obj, &$parms, array $optionOrder)
    {
        $type = get_class($obj);

        foreach ($optionOrder as $method => $hasParms)
        {
            if (array_key_exists($method, $parms))
            {              
                if ($hasParms)                      // Parameters needed...
                {
                    $parm = $parms[$method];
                    if (!is_array($parm))           // Parms must be an array
                    {
                        $parm = array($parm);
                    }
                    $plist = join(',', $parm);
                } else
                {
                    $parm = '';
                    $plist = 'none';
                }

                $setter = NULL;
                if (method_exists($obj, $method))   // Name IS function
                {
                    $setter = $method;
                    $calling = 'direct';
                } else
                {
                    $setter = 'set' . ucfirst($method);
                    $calling = 'setter';
                    if (!method_exists($obj, $setter)) // It's a setter function
                    {
                        $setter = NULL;
                    }
                }
                /**
                 * Call the setter function with the parameters passed
                 */
                if (null !== $setter) {               // Setter function exists
                    reset($parm);                 // force a reset of the index
                    // If we have an array-within-array, we are going to do
                    // an itertive call.
                    if (is_array($parm) && is_array($parm[key($parm)])) {
                        $this->printDebug(" ... multiple interations. Calling each");
                        foreach (array_keys($parm) as $key) {
                            $plist = join(',', $parm[$key]);
                            $this->printDebug("($calling) $type: $setter ($plist)\n");
                            call_user_func_array(array($obj, $setter), $parm[$key]);
                        }
                    } else {
                        call_user_func_array(array($obj, $setter), $parm);
                    }
                } else {

                    $this->printDebug("(ignore) $type: $method ($plist)\n");
                }
            }
        }
    }
}
