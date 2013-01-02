\LWare\Queue Message queue library
=====================================

The message services classes are styled after the SILEX framework. They allow a simple way to route messages based upon the AppID field in the AMQP header.  The system is built upon layers, which allow programmers to code to differing levels of complexity.

*MessageQueue*
--------------
A MessageQueue is the library that allows you to connect to the amqp library and maintain connections. It will attempt re-connects when required and provides a simpler interface than the standard PHP library

*MessageExceptions*
-------------------
This contains all of the exceptions that can occur in the library. There is no code here that is usable and programmers should avoid using these exceptions in their code.

*ApiProcess*
------------
The ApiProcess encapsulates each daemon process that will be called. It provides the run interfaces for calling and resolving parameter references. By itself there are only a few interfaces of interest and only in conjunction with MessageDaemon.

*MessageDaemon*
---------------
A daemon class instance binds call routines, queues and processing into one simple class. The class extends the MessageQueue class and uses ApiProcess to hold the process information.

*Setup*
-------
In order to use the services you must instantiate and initialise the service. All the services attempt to perform error handling for you. They provide fairly simple interfaces to setup the services. To use the MessageDaemon class you will need to know how to use the MessageQueue class.

*MessageQueue*
--------------
<pre> $srv = new MessageQueue();</pre>
Instantiate a new message queue object.

<pre>$srv->setConnection( ‘amqp://host’ );</pre>
Set the connection string for the host. This is split and handled for you in the object rather than having to set multiple values. The values are:
amqp://username:password@host:port/vhost
Any of these may be missing and will be filled in by the PHP defaults. (See PHP documentation at  http://php.net/manual/en/amqp.configuration.php .
*Connection*
------------
After setup, you can call getConnection() to retrieve an \AMQPConnection object. Unless you need the connection object, it is not necessary to create one; they will be created automatically for you. (Only call if you need the connection object. It is handled internally for you.)
$srv->getConnection();
*Channels*
----------
After setup, you can call getChannel() to retrieve an \AMQPChannel object. There is only one channel object per message service object. These are stored and checked internally to ensure they are valid and open. When you call getChannel if none are open, it will call getConnection() to create a new one. It is not necessary to call both. (Only call if you need the connection object. It is handled internally for you.)
<pre>$srv->getChannel();</pre>
*Exchange*
----------
Exchanges must exist before you can bind queues to them, so creation of an exchange may be needed even if you have no intention of publishing a message.
<pre>$srv->createExchange(array( ‘name’=>’xname’, ‘parm’ => ‘value’ ) );</pre>
The parameters are an array with key/value pairs, and the values may be simple variables or arrays. Each exchange must have a name attached to it. This is mandatory, even if it is blank (‘’).
There is no need to add declare as the exchange function will always issue a declare when it is created.  Options are:
* type => value (Required)
* name=>string (Required)
* flags => integer value
* argument => array( string key , mixed value)
* arguments => array( key => value , key=>value…)
* declare  => true (not required)

You cannot call delete from the createExchange() method.

