<?php
/*
 * ---------------
 * 
 * Copyright (c) 2008 Positive Networks, Inc.
 * 
 * Permission is hereby granted, free of charge, to any person
 * obtaining  a copy of this software and associated documentation
 * files (the "Software"),  to deal in the Software without
 * restriction, including without limitation the  rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT  SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,  ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER  DEALINGS IN THE SOFTWARE.
 * 
 * ---------------
*/

/* 
 * pf_auth.php: An SDK for authenticating with PhoneFactor.
 * version: 2.2
 */

$pf_elementNames = array();
$pf_elements     = array();
$pf_pfd_host     = 'pfd.phonefactor.net';
$pf_backup_hosts = array('pfd2.phonefactor.net');

// 
// pf_authenticate: authenticates using PhoneFactor.
// 
// Arguments:
//     1) $username: the username to be auth'd
//     2) $phone: the phone number to PhoneFactor authenticate
//     3) $country_code: the country code to use for the call.  defaults to 1.
//     4) $allow_int_calls: a boolean value that determines whether international
//        calls should be allowed.  defaults to false.  note that this only needs to 
//        be set to true if the call you are making is international, and thus could
//        cost you money.  see www.phonefactor.net for the PhoneFactor rate table
//        that shows which calling zones will cost money and which are free.
//     5) $hostname: the hostname this authentication is being sent from.
//                   defaults to 'pfsdk-hostname'
//     6) $ip: the ip address this authentication is being sent from.
//             defaults to '255.255.255.255'
//     7) $ca_path: a string representing the path on disk to the folder
//        containing ca certs to validate the PhoneFactor backend against.
//        if you don't use this, the PhoneFactor backend's certificate will not
//        be validated.
//     8) $ca_file: similar to the ca_path parameter, except that this should
//        be the path on disk to a file containing one or more ca certificates
//        to use for validation of server certificates
//     9) user_can_change_phone: if this is set to true, the users will be able to
//        change their phone number from the phone menu. If this is set to false they will
//        not be able to change the phone number.
// 
// Return value:
//     An array containing 3 elements:  a boolean value representing whether the auth
//     was successful or not, a string representing the status of the phonecall, and 
//     a string containing an error id if the connection to the PhoneFactor backend
//     failed.  If the authentication element is a true value, then the other two 
//     elements can safely be ignored.
// 
function pf_authenticate ($username, $phone, $pin, $country_code = '1', $allow_int_calls = false,
    $hostname = 'pfsdk-hostname', $ip = '255.255.255.255', 
    $ca_path = '', $ca_file = '',
    $user_can_change_phone = false)
{
  $message = create_authenticate_message(
    $username, 
    $phone, 
    $country_code, 
    $allow_int_calls, 
    $hostname, 
    $ip,
    $user_can_change_phone,
    $pin);
  
  $response = send_message($message, $ca_path, $ca_file);

  return get_response_status($response);
}

// 
// create_authenticate_message: generates an authenticate message to be sent
//   to the PhoneFactor backend.
//  
// Arguments:
//     1) $username: the username to be auth'd
//     2) $phone: the phone number to PhoneFactor authenticate
//     3) $country_code: the country code to use for the call.  defaults to 1.
//     4) $allow_int_calls: boolean value that determines whether international 
//        calls should be allowed. 
//     5) $hostname: the hostname this authentication is being sent from
//     6) $ip: the ip address this authentication is being sent from
//     7) $user_can_change_phone: can user change their phonenumber
// 
// Return value:
//     a complete authentication xml message ready to be sent to the PhoneFactor backend
// 
function create_authenticate_message ($username, $phone, $country_code, $allow_int_calls, $hostname, $ip, $user_can_change_phone, $pin)
{
  $xml = "
    <pfpMessage>
      <header>
        <source>
          <component type='pfsdk'>
            <host ip='$ip' hostname='$hostname'/>
          </component>
        </source>
      </header>

      <request request-id='" . rand(0, 10000) . "'>
        <authenticationRequest>
          <customer>
            <licenseKey>
              KGD2PUSY8PW1
            </licenseKey>
            <groupKey>
              967cb9247023ea33a52e1c9699dfa399
            </groupKey>
          </customer>

          <countryCode>
            $country_code
          </countryCode>
          <authenticationType>
            pfsdk
          </authenticationType>
          <username>
            $username
          </username>
          <phonenumber userCanChangePhone='" . ($user_can_change_phone ? 'yes' : 'no') . "'>
            $phone
          </phonenumber>
          <allowInternationalCalls>
            " . ($allow_int_calls ? 'yes' : 'no') . "
          </allowInternationalCalls>
          <pinInfo pinMode='pin'>
            <pin pinFormat='plainText' pinChangeRequired='no'>$pin</pin>
          </pinInfo>
          <userCanChangePin>
            no
          </userCanChangePin>
        </authenticationRequest>
      </request>
    </pfpMessage>
  ";

  return $xml;
}

// 
// send_message: sends a message to the PhoneFactor backend
// 
// Arguments:
//     1) $message: the message to be sent
//     2) $ca_path: a string representing the path on disk to the folder
//        containing ca certs to validate the PhoneFactor backend against
//     3) $ca_file: similar to the ca_path parameter, except that this should
//        be the path on disk to a file containing one or more ca certificates
//        to use for validation of server certificates
// 
// Return value:
//     The response text from the PhoneFactor backend.  This will
//     likely be an XML message ready to be parsed.  Note that the 
//     return value could be NULL if the communication with the 
//     backend was not possible.
// 
function send_message($message, $ca_path, $ca_file)
{
  global $pf_pfd_host, $pf_backup_hosts;

  $tries = count($pf_backup_hosts);
  $i     = 0;

  do
  {
    $curl = setup_curl_connection($message, $ca_path, $ca_file);

    $doc = curl_exec($curl);

    if (curl_errno($curl))
      print curl_error($curl) . "\n";
    
    if ($doc == FALSE)
    {
      array_push($pf_backup_hosts, $pf_pfd_host);
      $pf_pfd_host = array_shift($pf_backup_hosts);
      $i++;
    }
    else
      break;
  } while($i <= $tries);

  curl_close($curl);

  return $doc;
}

function setup_curl_connection($message, $ca_path, $ca_file)
{
  global $pf_pfd_host;

  $validate = (strlen($ca_path) > 0 || strlen($ca_file) > 0 ? TRUE : FALSE);
  $curl     = curl_init("https://$pf_pfd_host/pfd/pfd.pl");

  $curl_options = array(
    CURLOPT_PORT           => '443',
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $message,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_SSL_VERIFYPEER => $validate,
    CURLOPT_CAPATH         => $ca_path,
    CURLOPT_CAINFO         => $ca_file,
    CURLOPT_SSLCERT        => dirname(__FILE__) . '/certs/cert.pem',
    CURLOPT_SSLKEY         => dirname(__FILE__) . '/certs/pkey.pem',
  );

  foreach ($curl_options as $option => $value)
    curl_setopt($curl, $option, $value);
  
  return $curl;
}

// 
// startElement: handler for the beginning of an XML element
// 
// Arguments:
//     1) $parser: a reference to the XML parser
//     2) $name: the name of the XML element being parsed
//     3) $attrs: the attributes found in this element
// 
// Return value:
//     none
// 
function startElement ($parser, $name, $attrs)
{
    global $pf_elementNames, $pf_elements;
    $pf_elementNames[] = "$name";

    $pf_elements[$name]['attrs'] = array();

    foreach ($attrs as $key => $value)
    {
        $pf_elements[$name]['attrs'][$key] = $value;
    }
}

// 
// endElement: handler for the end of an XML element
// 
// Arguments:
//     1) $parser: a reference to the XML parser
//     2) $name: the name of the XML element being parsed
// 
// Return value:
//     none
// 
function endElement ($parser, $name)
{
}

// 
// characterData: handler for character data
// 
// Arguments:
//     1) $parser: a reference to the XML parser
//     2) $data: the character data between element tags
// 
// Return value:
//     none
// 
function characterData ($parser, $data)
{
    global $pf_elementNames, $pf_elements;
    $name = array_pop($pf_elementNames);

    $pf_elements[$name]['data'] = trim($data);
}

// 
// get_response_status: parses the response from the PhoneFactor backend
// 
// Arguments:
//     1) $response: the XML response string to be parsed
// 
// Return value:
//     Same as the return value for pf_authenticate
// 
function get_response_status ($response)
{
    global $pf_elements;

    if (!$response)
        return array(false, 0, 0);

    $disposition = false;
    $authenticated = false;
    $call_status = 0;
    $error_id = 0;
    $ret = false;

    $xml_parser = xml_parser_create();

    xml_set_element_handler($xml_parser, 'startElement', 'endElement');
    xml_set_character_data_handler($xml_parser, 'characterData');

    xml_parse($xml_parser, $response);
    xml_parser_free($xml_parser);

    if ($pf_elements['STATUS']['attrs']['disposition'] == 'success')
        $disposition = true;
    else
        $ret = false;

    if ($pf_elements['AUTHENTICATED']['data'] == 'yes')
    {
        $authenticated = true;
        $ret = true;
    }
    else
        $ret = false;

    $call_status = $pf_elements['CALLSTATUS']['data'];
    $error_id = $pf_elements['ERROR-ID']['data'];

    return array($ret, $call_status, $error_id);
}
?>
