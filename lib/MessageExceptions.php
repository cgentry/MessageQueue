<?php
namespace LWare\Queue;
/**
 *  All of the exceptions live here
 *
 * These are the exceptions we are going to thro
 *
 * @author    Charles Gentry <cg-lware@charlesgentry.com>
 * @category  LWare
 * @package   Queue
 * @copyright Copyright (c) 2012 CGentry.
 * @version   1.0
 * @since     2012-Dec-11
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
//  @codeCoverageIgnoreStart
class MessageException      extends \Exception {};
class MsgExceptionExchange  extends MessageException {}
class MsgExceptionQueue     extends MessageException {}
class MsgExceptionApi       extends MessageException {}

// @codeCoverageIgnoreEnd

