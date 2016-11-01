<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
namespace NetDns2\RR;
/**
 * DNS Library for handling lookups and updates. 
 *
 * PHP Version 5
 *
 * Copyright (c) 2013, Mike Pultz <mike@mikepultz.com>.
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
 * @category  Networking
 * @package   Net_DNS2
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2013 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/Net_DNS2
 * @since     File available since Release 1.3.1
 *
 */

/**
 * L32 Resource Record - RFC6742 section 2.2
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |          Preference           |      Locator32 (16 MSBs)      |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |     Locator32 (16 LSBs)       |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 * @category Networking
 * @package  Net_DNS2
 * @author   Mike Pultz <mike@mikepultz.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://pear.php.net/package/Net_DNS2
 * @see      \NetDns2\RR
 *
 */
class L32 extends \NetDns2\RR
{
    /*
     * The preference
     */
    public $preference;

    /*
     * The locator32 field
     */
    public $locator32;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        return $this->preference . ' ' . $this->locator32;
    }

    /**
     * parses the rdata portion from a standard DNS config line
     *
     * @param array $rdata a string split line of values for the rdata
     *
     * @return boolean
     * @access protected
     *
     */
    protected function rrFromString(array $rdata)
    {
        $this->preference = array_shift($rdata);
        $this->locator32 = array_shift($rdata);

        return true;
    }

    /**
     * parses the rdata of the \NetDns2\Packet object
     *
     * @param \NetDns2\Packet &$packet a \NetDns2\Packet packet to parse the RR from
     *
     * @return boolean
     * @access protected
     * 
     */
    protected function rrSet(\NetDns2\Packet &$packet)
    {
        if ($this->rdlength > 0) {

            //
            // unpack the values
            //
            $x = unpack('npreference/C4locator', $this->rdata);

            $this->preference = $x['preference'];

            //
            // build the locator value
            //
            $this->locator32 = $x['locator1'] . '.' . $x['locator2'] . '.' .
                $x['locator3'] . '.' . $x['locator4'];

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     * 
     * @param \NetDns2\Packet &$packet a \NetDns2\Packet packet use for
     *                                 compressed names
     *
     * @return mixed                   either returns a binary packed 
     *                                 string or null on failure
     * @access protected
     * 
     */
    protected function rrGet(\NetDns2\Packet &$packet)
    {
        if (strlen($this->locator32) > 0) {

            //
            // break out the locator value
            //
            $n = explode('.', $this->locator32);

            //
            // pack the data
            //
            return pack('nC4', $this->preference, $n[0], $n[1], $n[2], $n[3]);
        }

        return null;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */