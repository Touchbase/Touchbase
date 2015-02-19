<?php

/**
 *  Copyright (c) 2013 William George.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 *  @author William George
 *  @package Parse
 *  @category Control
 *  @date 20/07/2014
 */

namespace Touchbase\API\Client\Provider;

defined('TOUCHBASE') or die("Access Denied.");

use Artax\AsyncClient;
use Alert\ReactorFactory;
use Touchbase\API\Client\ClientInterface;
use Touchbase\Core\Config\Store as ConfigStore;

class ArtaxClientProvider implements ClientInterface
{
	
	public function __construct(){}
	
	public function request(){
	
		$reactor = (new ReactorFactory)->select();
		$client = new AsyncClient($reactor);
	
		$onComplete = function($response, Request $request) use ($uri){
			unset($this->processingUris["$uri"]);

			if($this->followLinks && $response->getStatus() == 200){
	            $this->parseLinksFromRawBody((new Uri($request->getUri())), $response->getBody());
        	}

			$this->dequeueNextRequest();
	        if(!--$this->outgoingRequests){
	        	$this->reactor->stop();
	        }

        	if($response instanceof \Exception){
        		$this->failedUris["$uri"] = TRUE;

	        	$this->onError($request, $response);
	        } else {
	        	$this->completedUris["$uri"] = TRUE;

		        $this->onResponse($request, $response);
	        }
        };

        $this->outgoingRequests++;
        $this->client->request($uri, $onComplete, $onComplete);
        
	}
	
	public function cancel(){}
	
	public function cancelAll(){}

}