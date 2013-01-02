<?php

namespace LWare\Queue;
/**
 *  This is the final API processor
 *
 * Add this in as the final default processor in order to perform an ack
 * or any other tasks that you want to put in.
 *
 * @author    Charles Gentry <cg-lware@charlesgentry.com>
 * @category  Queue
 * @package   LWare\Queue
 * @copyright Copyright (c) 2012 CGentry
 * @version   1.0
 * @since     2012-Dec-18
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


class ApiDefaultHandler
{
    public function ack( \AMQPQueue $q , \AMQPEnvelope $env )
    {
        $q->ack( $env->getDeliveryTag() );
    }
}

