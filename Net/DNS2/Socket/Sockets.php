<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * DNS Library for handling lookups and updates. 
 *
 * PHP Version 5
 *
 * Copyright (c) 2010, Mike Pultz <mike@mikepultz.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Mike Pultz nor the names of his contributors 
 *     may be used to endorse or promote products derived from this 
 *     software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category	Networking
 * @package		Net_DNS2
 * @author		Mike Pultz <mike@mikepultz.com>
 * @copyright	2010 Mike Pultz <mike@mikepultz.com>
 * @license		http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version		SVN: $Id$
 * @link		http://pear.php.net/package/Net_DNS2
 * @since		File available since Release 1.0.0
 *
 */

/**
 * Socket handling class using the PHP sockets extension
 *
 * The sockets extension is faster than the stream functions in PHP, but it's
 * not standard. So if the extension is loaded, then this class is used, if
 * it's not, then the Net_DNS2_Socket_Streams class is used.
 *
 * @package		Net_DNS2
 * @author		Mike Pultz <mike@mikepultz.com>
 * @see			Net_DNS2_Socket
 *
 */
class Net_DNS2_Socket_Sockets extends Net_DNS2_Socket
{
    /**
     * opens a socket connection to the DNS server
     *
	 * @return	boolean
     * @access	public
     *
     */
	public function open()
	{
		//
		// create the socket
		//
		$this->_sock = @socket_create(AF_INET, $this->_type, ($this->_type == SOCK_STREAM) ? SOL_TCP : SOL_UDP);
		if ($this->_sock === FALSE) {

			$this->last_error = socket_strerror(socket_last_error());
			return false;
		}

		@socket_set_option($this->_sock, SOL_SOCKET, SO_REUSEADDR, 1);

		//
		// bind to a local IP/port if it's set
		//
		if (strlen($this->_local_host) > 0) {

			if (@socket_bind($this->_sock, $this->_local_host, ($this->_local_port > 0) ? $this->_local_port : null) === FALSE) {

				$this->last_error = socket_strerror(socket_last_error());
				return false;
			}
		}

		//
		// connect to the socket
		//
		if (@socket_connect($this->_sock, $this->_host, $this->_port) === FALSE) {

			$this->last_error = socket_strerror(socket_last_error());
			return false;
		}

		//
		// mark the socket as non-blocking
		//
		if (@socket_set_nonblock($this->_sock) === FALSE) {

			$this->last_error = socket_strerror(socket_last_error());
			return false;
		}

		return true;
	}

    /**
     * closes a socket connection to the DNS server
     *
	 * @return	boolean
     * @access	public
     *
     */
	public function close()
	{
		if (is_resource($this->_sock) === TRUE) {

			@socket_close($this->_sock);
		}
		return true;
	}

    /**
     * writes the given string to the DNS server socket
     *
	 * @param	string	$data		a binary packed DNS packet
	 * @return	boolean
     * @access	public
     *
     */
	public function write($data)
	{
		$read 	= NULL;
		$write 	= array($this->_sock);
		$except = NULL;

		//
		// select on write
		//
		switch(@socket_select($read, $write, $except, $this->_timeout))
		{
			case false:
				$this->last_error = socket_strerror(socket_last_error());
				return false;
			break;
			case 0:
				return false;
			break;
			default:
				;
		}

		//
		// if it's a TCP socket, then we need to packet and send the length of the
		// data as the first 16bit of data.
		//
		if ($this->_type == SOCK_STREAM) {

			// TODO: get rid of pack()

			$length = pack('n', strlen($data));

			$s = strlen($data);

echo "length=" . base64_encode($length) . ", " . $s . "\n";

$r = ($s << 16);

echo "length=" . base64_encode($r) . ", " . $s . "\n";

			if (@socket_write($this->_sock, $length) === FALSE) {

				$this->last_error = socket_strerror(socket_last_error());
				return false;
			}
		}

		//
		// write the data to the socket
		//
		$size = @socket_write($this->_sock, $data);
		if ( ($size === FALSE) || ($size != strlen($data)) ) {

			$this->last_error = socket_strerror(socket_last_error());
			return false;
		}

		return true;
	}

    /**
     * reads a response from a DNS server
     *
	 * @param	integer	$size		the size of the DNS packet read is passed back
	 * @return	mixed				returns the data on success and false on error
     * @access	public
     *
     */
	public function read(&$size)
	{
		$read 	= array($this->_sock);
		$write 	= NULL;
		$except = NULL;

		//
		// select on read
		//
		switch(@socket_select($read, $write, $except, $this->_timeout))
		{
			case false:
				$this->last_error = socket_strerror(socket_last_error());
				return false;
			break;
			case 0:
				return false;
			break;
			default:
				;
		}
	
		$data = '';
		$length = 512;

		//
		// if it's a TCP socket, then the first two bytes is the length of the DNS
		// packet- we need to read that off first, then use that value for the 
		// packet read.
		//
		if ($this->_type == SOCK_STREAM) {

			if (($size = @socket_recv($this->_sock, $data, 2, 0)) === FALSE) {

				$this->last_error = socket_strerror(socket_last_error());
				return false;
			}
			
			// TODO: get rid of unpack()

			$x = unpack('nlength', $data);
			$data = '';

			$length = $x['length'];
			if ($length < 12) {
				return false;
			}
		}

		//
		// read the data from the socket
		//
		if (($size = @socket_recv($this->_sock, $data, $length, MSG_WAITALL)) === FALSE) {

			$this->last_error = socket_strerror(socket_last_error());
			return false;
		}

		return $data;
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
