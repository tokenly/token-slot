<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">


    <title>Token Slot - Token Redemption</title>
    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/tokenslot.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>
	<div class="header-container">
		<div class="container">
		  <div class="header  clearfix">
			<nav>
			  <ul class="nav nav-pills pull-right">
				<li role="presentation"><a href="#intro">Intro</a></li>
				<li role="presentation"><a href="#usage">Usage</a></li>
				<li role="presentation"><a href="#api">API</a></li>
				<li role="presentation"><a href="#contact">Contact</a></li>
				<li role="presentation"><a href="https://github.com/tokenly/token-slot" target="_blank" class="no-anchor"><i class="fa fa-github-alt"></i> Github</a></li>
			  </ul>
			</nav>
			<h3 class="text-muted"><a href="https://letstalkbitcoin.com/blog/tokenly" target="_blank"><img src="images/tokenly-logo.png" alt="" style="width: 140px;" /></a></h3>
		  </div>
		</div>
	</div>
	<div class="main-container">
	<div class="container">
      <div class="jumbotron">
        <h1>Token Slot</h1>
        <p class="lead">Bitcoin and Counterparty token redemption service.</p>
        <!--<p><a class="btn btn-lg btn-success" href="#" role="button">Sign up today</a></p>-->
      </div>

      <div class="row marketing">
        <div class="col-lg-12">
			<a name="intro"></a>
			<h2>Introduction</h2>
			<p>
				Token Slot is a cryptographic token redemption and payment processing service, currently with support for the
				Bitcoin (BTC) and Counterparty (XCP) protocols. This service can be used to allow for easy token redemption
				on any platform that connects to our API. All payments received by the system are forwarded to an address fully in your
				control on a daily basis, so there is minimal holding time for your funds.
			</p>
			<p>
				The term "slot" is a reference to coin slots in vending machines, old arcade games, pay phones etc..
				The basic idea is to insert X amount of tokens in to a machine/application in order to make it do something.
				This could be anything from a simple paywall or redemption for a physical product, to something more complex. 
			</p>
			<p>
				Used in combination with <a href="https://github.com/tokenly/swapbot" target="_blank">SwapBot</a> and other Tokenly tools, a
				fully tokenized redeemable product ecosystem can emerge. 
			</p>
			<hr>
			<a name="usage"></a>
			<h2>Basic Usage</h2>
			<p>
				For integration into your application, we recommend you use one of our libraries for connecting to the Token Slot API.
				Currently, we only have this library available in PHP (more languages coming soon). You can find the 
				<a href="https://github.com/tokenly/tokenslot-client" target="_blank">standalone PHP class on github</a>.
				You can also find a version adapted for the Tokenly CMS 
				<a href="https://github.com/tokenly/tokenly-cms/blob/master/slick/API/TokenSlotClient.php" target="_blank">here</a>.
			</p>
			<h4>Getting Started</h4>
			<p>
				To get started, you will need to include the Client class in your project. You can use 
				Composer, or just include the class file directly. 
			</p>
			<p>
				Via Composer, include the following in your <em>composer.json</em> file.
			</p>
<pre>
	"require": {
		"tokenly/tokenslot-client": "dev-master@dev"
	},
</pre>
			<p>
				In your application, create a new instance of the TokenSlot-Client class. 
			</p>
<pre>
	(if using composer package)
	$tokenslot = new \Tokenly\TokenSlotClient\Client(API_URL, API_KEY);
	
	(if using Tokenly CMS)
	$tokenslot  = new \API\TokenSlotClient(API_URL, API_KEY);
</pre>
			<p>
				API_URL = https://slots.tokenly.com/api/v1<br>
				API_KEY = your client API key
			</p>
			<h4>Creating Payment Requests</h4>
			<p>
				The primary functionality of Token Slot is as a means of accepting tokens as payment for something,
				whether that be a physical product, a digital download, access to content or whatever else.
				When a new order or redemption is created in your application, Token Slot generates a fresh bitcoin address, unique to the customer.
				When the correct amount of tokens is received to the payment address, the payment request is marked as complete, and you may 
				continue on to finalize the redemption (e.g, ship the product). All tokens received are regularly forwarded to 
				an address of your choosing, twice a day. 
			</p>
			<p>
				<Strong>Step 1:</Strong>
			</p>
			<p>
				Before creating payment requests, you first need to create a "slot" for
				accepting a specific type of token. This tells the system where you want this type of
				token to go (if different than primary account forwarding address), how many confirmations
				required before payments are considered complete, among other options. See the API reference below
				for further details.
			</p>
			<p>
				TokenSlot-Client provides a simple method for configuring a new slot before initializing new payments.
			</p>
<pre>
	$slot_alias = 'myslot_alias';
	$accepted_tokens = array('TOKENLY');
	$min_conf = 1;
	$slot = $tokenslot->getOrCreateSlot($slot_alias, $accepted_tokens, $min_conf)
</pre>
			<p><br>
				<strong>Step 2:</strong>
			</p>
			<p>
				Initialize a new payment request:
			</p>
<pre>
	//convert total to satoshi format by multiplying by 100,000,000
	$total = 1 * SATOSHI_MOD; 
	$payment_token = 'TOKENLY';
	$create_payment = $tokenslot->newPayment($slot['public_id'], $payment_token, $total);
	if($create_payment){
		$payment_id = $create_payment['payment_id'];
		$payment_address = $create_payment['address'];
		//save this information somewhere and display address to customer
	}
	else{
		//payment request failed
	}
</pre>
			<p><br>
				<strong>Step 3:</strong>
			</p>
			<p>
				After a payment request is created, there are two ways that you can check if 
				the customer has completed their payment.
			</p>
			<p>
				The first method is by simply querying the API.
			</p>
<pre>
	$check_complete = $tokenslot->checkPaymentComplete($payment_address);
	if($check_complete){
		//complete the order
	}
	else{
		//they have not paid the full amount yet
	}

</pre>
			<p>
				The second method is by using webhooks. This requires the "webhook" field in your slot to be configured.
				Token Slot will post a notification (in JSON format) to your defined webhook whenever it sees a new incoming payment,
				or that payment has gained new network confirmations. 
			</p>
<pre>
	$process_webhook = $tokenslot->receivePaymentsWebhook();
	if($process_webhook){
		//make sure data matches up to a pending redemption in your local database
		
		if($process_webhook['complete']){
			//complete order logic
		}
		else{
			//order not yet paid or not enough confirmations
		}
	}

</pre>
<br>
			<h4>Demos</h4>
			<p>
				<a href="https://letstalkbitcoin.com/tokenslot-demo" target="_blank">Click here</a> to view a basic front-end tech demo. (<a href="https://github.com/tokenly/tokenly-cms/blob/master/slick/Tags/TokenSlotDemo.php" target="_blank">view source code</a>)
			</p>
			<p>
				Multiple token redemption w/ email notification (coming shortly)
			</p>
			<hr>
			<a name="api"></a>
			<h2>API Reference</h2>
			<p>
				In order to access the API, you must first obtain an API key. Please <a href="#contact">contact us</a> during this beta
				period if you would like to become an early use case.
			</p>
			<p>
				All API methods accept requests and return responses in JSON format.
				If an error occurs, an appropriate HTTP status code is given (e.g 400, 403, 500),
				and an "error" field is included in the JSON response. 
			</p>
			<p>
				API endpoints look like this: <strong>https://slots.tokenly.com/api/v1/{endpoint/method}?key={my API key}</strong>
			</p>
			<strong>Quick links:</strong>
			<ul>
				<li><a href="#api-methods">API Methods</a></li>
				<li><a href="#webhooks">Webhook Notifications</a></li>
				<li><a href="#response-obs">Response Objects</a></li>
			</ul>
			<a name="api-methods"></a>
			<h3>Methods</h3>
			<ul class="api-list">
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api1">/api/v1/payments/request/{slot_id} [GET]</a>
					<div id="api1" class="collapse in">
						<p>
							Creates a new payment request and deposit address for the customer.
						</p>
						<strong>Request method:</strong> GET<br>
						<strong>Parameters:</strong>
						<ul>
							<li>token (string)</li>
							<li>total (integer) - optional</li>
							<li>reference (string) - optional</li>
							<li>peg (string) - optional</li>
							<li>peg_value (integer) - optional
						</ul>
						<strong>Returns:</strong>
						<ul>
							<li>Payment Request Object</li>
						</ul>
						<p>
							The <em>token</em> you wish to accept for this payment request must be
							in the list of accepted tokens for your slot. 
						</p>						
						<p>
							The <em>total</em> field should be the total amount of the order, in satoshis.
							If total is set to 0, it assumes a "pay what you want" type situation, and any 
							valid transaction seen will cause the payment to be marked complete.
						</p>
						<p>
							The <em>reference</em> field can be used to store a custom identifier
							for the payment request, and should be unique per payment.
						</p>
						<p>
							You may use the <em>peg</em> and <em>peg_total</em> fields to create a payment request
							where the total value is pegged to the price of some other token or currency.
							For example, if you set token = LTBCOIN, peg = USD and peg_value = 1000, a <em>total</em> will be automatically generated 
							for the equivilant of $10.00 worth of LTBCOIN. Cryptocurrency peg values should be measured in satoshis, and Fiat values
							in cents. The <em>total</em> does not need to be included when using pegged prices, since it is generated for you.
						</p>
						<p>
							A list of supported peggable tokens and currencies can be found <a href="https://github.com/tokenly/token-slot/blob/master/config/settings.php" target="_blank">here</a>.
						</p>
						<p>
							The <em>{slot_id}</em> in the endpoint URL can be either the slot's unique ID or
							assigned "nickname", which can be used as an alias to the public ID. 
						</p>

					</div>
				</li>
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api2">/api/v1/payments/{payment_id} [GET]</a>
					<div id="api2" class="collapse in">
						<p>
							Gets the most up to date details on a specific existing payment request.
						</p>
						<strong>Request method:</strong> GET<br>
						<strong>Parameters:</strong> None<br>
						<strong>Returns:</strong>
						<ul>
							<li>Payment Detail Object</li>
						</ul>	
					</div>
				</li>			
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api3">/api/v1/payments/{payment_id}/cancel [POST]</a>
					<div id="api3" class="collapse in">
						<p>
							Cancels a payment request, which will stop Token Slot from monitoring new payments and mark it as "cancelled".
						</p>
						<strong>Request method:</strong> POST<br>
						<strong>Parameters:</strong> None<br>
						<strong>Returns:</strong>
						<ul>
							<li>result (boolean)</li>
						</ul>	
						<p><em>result</em> field will be true if successful.</p>
					</div>
				</li>
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api4">/api/v1/payments/all [GET]</a>
					<div id="api4" class="collapse in">
						<p>
							Retrieves a list of all payment requests associated with your client account / API key.
						</p>
						<strong>Request method:</strong> GET<br>
						<strong>Parameters:</strong>
						<ul>
							<li>incomplete (boolean) - optional</li>
							<li>cancelled (boolean) - optional</li>
						</ul>
						<strong>Returns:</strong>
						<ul>
							<li>Array of Payment Detail Objects</li>
						</ul>	
						<p>
							If <em>incomplete</em> field is included and set to "true", then only 
							payments which are not yet complete will be included. If set to "false", then it
							will return only completed payments. Exclude the <em>incomplete</em> field to get both.
						</p>
						<p>
							If <em>cancelled</em> is set to true, then closed/cancelled payment requests will be 
							included in the response. This is false by default.
						</p>
					</div>
				</li>								
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api5">/api/v1/slots [GET]</a>
					<div id="api5" class="collapse in">
						<p>
							Retrieves a list of all "token slots" associated with your client account / API key.
						</p>
						<strong>Request method:</strong> GET<br>
						<strong>Parameters:</strong> None<br>
						<strong>Returns:</strong>
						<ul>
							<li>Array of Slot Objects</li>
						</ul>	
					</div>
				</li>	
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api6">/api/v1/slots [POST]</a>
					<div id="api6" class="collapse in">
						<p>
							Creates a new "token slot" attached to your account, for use with creating payment requests.
						</p>
						<strong>Request method:</strong> POST<br>
						<strong>Parameters:</strong>
						<ul>
							<li>tokens (array)</li>
							<li>webhook (string) - optional</li>
							<li>forward_address (string) - optional</li>
							<li>min_conf (integer) - optional (defaults 0)</li>
							<li>label (string) - optional</li>
							<li>nickname (string) - optional</li>
						</ul>
						<strong>Returns:</strong>
						<ul>
							<li>Slot Object</li>
						</ul>
						<p>
							The <em>tokens</em> field is an array of tokens which this slot will accept.
							They can be either BTC, or any token built on Counterparty, including XCP.
						</p>
						<p>
							<em>webhook</em> is the URL that you want the Token Slot service to send payment notifications to.
							Payment notifications are sent via a POST request using JSON formatting.
						</p>
						<p>
							<em>forward_address</em> is the Bitcoin address which you would like incoming tokens
							to be automatically forwarded to. Tokens received from payment requests are sent out
							twice a day, so we never hold your funds for long periods of time. If this field is left blank,
							your default forwarding address defined in your account will be used.
						</p>
						<p>
							<em>min_conf</em> is the minimum number of confirmations required before 
							the system will mark a payment request as complete and stop sending notifications to your webhook.
							This number can be 0 or more...
							please use 0 confirmation transactions responsibly (do not accept 0-conf payments for a 
							product or service which you cannot reverse in the event of an attempted double spend).
						</p>
						<p>
							The <em>label</em> field allows you to assign an internal reference label to the slot. This
							is for personal reference only.
						</p>
						<p>
							<em>nickname</em> allows you to set an alias for the slot, which can be used instead of the 
							unique {public_id}. Example endpoint using a slot alias: <em>/api/v1/payments/request/coffeepound_one</em>
						</p>
					</div>
				</li>							
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api7">/api/v1/slots/{slot_id} [GET]</a>
					<div id="api7" class="collapse in">
						<p>
							Gets the basic details on a particular "slot"
						</p>
						<strong>Request method:</strong> GET<br>
						<strong>Parameters:</strong> None<br>
						<strong>Returns:</strong>
						<ul>
							<li>Slot Object</li>
						</ul>	
					</div>
				</li>
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api8">/api/v1/slots/{slot_id} [PATCH]</a>
					<div id="api8" class="collapse in">
						<p>
							Updates slot details
						</p>
						<strong>Request method:</strong> PATCH<br>
						<strong>Parameters:</strong>
						<ul>
							<li>tokens (array) - optional</li>
							<li>webhook (string) - optional</li>
							<li>min_conf (integer) - optional</li>
							<li>forward_address (string) - optional</li>
							<li>label (string) - optional</li>
							<li>nickname (string) - optional</li>
						</ul>
						<strong>Returns:</strong>
						<ul>
							<li>Slot Object</li>
						</ul>	
					</div>
				</li>		
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api9">/api/v1/slots/{slot_id} [DELETE]</a>
					<div id="api9" class="collapse in">
						<p>
							Deletes a "token slot" from the system.
						</p>
						<strong>Request method:</strong> DELETE<br>
						<strong>Parameters:</strong> None<br>
						<strong>Returns:</strong>
						<ul>
							<li>result (boolean)</li>
						</ul>	
						<p class="text-danger">
							Warning: this will also remove all associated payment requests.
						</p>
					</div>
				</li>					
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api10">/api/v1/slots/{slot_id}/payments [GET]</a>
					<div id="api10" class="collapse in">
						<p>
							Gets a list of all payment requests submitted for the specified slot.
						</p>
						<strong>Request method:</strong> GET<br>
						<strong>Parameters:</strong>
						<ul>
							<li>incomplete (boolean) - optional</li>
							<li>cancelled (boolean) - optional</li>
						</ul>
						<strong>Returns:</strong>
						<ul>
							<li>Array of Payment Detail Objects</li>
						</ul>	
						<p>
							If <em>incomplete</em> field is included and set to "true", then only 
							payments which are not yet complete will be included. If set to "false", then it
							will return only completed payments. Exclude the <em>incomplete</em> field to get both.
						</p>
						<p>
							If <em>cancelled</em> is set to true, then closed/cancelled payment requests will be 
							included in the response. This is false by default.
						</p>						
					</div>
				</li>		
			</ul>
			<a name="webhooks"></a>
			<h3>Webhook Notifications</h3>
			<p>
				Token Slot automatically sends out notifications to your application each time it sees a transaction received
				for a payment request which you have made. The receiving notification end of your application is known as a "webhook".
			</p>
			<p>
				When a payment request is initiated, Token Slot monitors (via XChain) the payment address for any
				newly received transactions. As soon as it is seen (even with 0 confirmations), a JSON formatted POST request
				is sent to the webhook defined in your "slot". An additional notification is sent each time something has changed with the payment
				(e.g additional confirmations have occurred). The webhook notifications stop being sent once the payment request
				has been completed or cancelled. If for some reason contacting the webhook fails (servers down?), it will retry a minute or so later, up to 30 times before 
				giving up. 
			</p>
			<strong>Payment Notification Fields:</strong>
			<ul>
				<li>id (integer)</li>
				<li>time (timestamp)</li>
				<li>attempt (integer)</li>
				<li>payload (object)
					<ul>
						<li>payment_id (integer)</li>
						<li>slot_id (string)</li>
						<li>reference (string)</li>
						<li>payment_address (string)</li>
						<li>asset (string)</li>
						<li>total (float)</li>
						<li>total_satoshis (integer)</li>
						<li>received (float)</li>
						<li>received_satoshis (integer)</li>
						<li>confirmations (integer)</li>
						<li>init_date (timestamp)</li>
						<li>complete (boolean)</li>
						<li>complete_date (timestamp)</li>
						<li>tx_info (array of Transaction Info Objects)</li>
					</ul>
				</li>

			</ul>
			<strong>Sample JSON:</strong>
			<pre>
{  
   "id":329,
   "time":"2015-05-20T17:44:39+00:00",
   "attempt":1,
   "payload":"{\"payment_id\":\"36\",\"slot_id\":\"xiXyx5X2G3G3CIS1mg98\",\"reference\":\"0\",\"payment_address\":\"1Pck9kYC9fMjvahBhKrttiiWdjC2bxk1pT\",\"asset\":\"TOKENLY\",\"total\":1,\"total_satoshis\":\"100000000\",\"received\":1,\"received_satoshis\":100000000,\"confirmations\":0,\"init_date\":\"2015-05-20 17:44:28\",\"complete\":true,\"complete_date\":\"2015-05-20 17:44:39\",\"tx_info\":[{\"sources\":[\"1KthnhXAWmD6TuyjK3KbswVWvkuK3i2Keq\"],\"txid\":\"6755cc62874303303ae31a1303fd2b76c3db12dbb942abf053d334b1e051ac39\",\"amount\":100000000,\"confirmations\":0}]}"
}
			</pre>
			<a name="response-obs"></a>
			<h3>Response Objects</h3>
			<p>
				Below you can find a list of the possible objects that may be returned by the RESTful API.
			</p>
			<ul class="api-list">
				<li>
					<a class="btn" data-toggle="collapse" data-target="#ob1">Payment Request</a>
					<div id="ob1" class="collapse in">
						<strong>Fields:</strong>
						<ul>
							<li>payment_id (integer)</li>
							<li>address (string)</li>
							<li>total (integer)*</li>
							<li>peg (string)*</li>
							<li>peg_value (integer)*</li>
						</ul>
						<strong>Sample JSON:</strong>
						<pre>
{
    "payment_id": 34,
    "address": "1CooQ7k4gApPeYsywTy5dpmBuB9Yqt9o3c",
    "total": 6366183636363,
    "peg": "USD",
    "peg_value": 1000    
}
						</pre>	
						<p>
							<em>*note - total, peg and peg_value fields only available when using the price pegging feature</em>
						</p>						
					</div>
				</li>
				<li>
					<a class="btn" data-toggle="collapse" data-target="#ob2">Payment Detail</a>
					<div id="ob2" class="collapse in">
						<strong>Fields:</strong>
						<ul>
							<li>id (integer)</li>
							<li>address (string)</li>
							<li>token (string)</li>
							<li>total (integer)</li>
							<li>received (integer)</li>
							<li>peg (string)</li>
							<li>peg_value (integer)</li>
							<li>complete (boolean)</li>
							<li>init_date (timestamp)</li>
							<li>complete_date (timestamp)</li>
							<li>reference (string)</li>
							<li>tx_info (array of Transaction Info Objects)</li>
							<li>slot_id (string)</li>
							<li>cancelled (boolean)</li>
							<li>cancel_time (timestamp)</li>
						</ul>
						<p>
							A payment request can receive multiple transactions,
							but all transactions must total up to the required amount and 
							all have at least the <em>min_conf</em> amount of confirmations.
						</p>
						<p>
							The <em>total</em> and <em>received</em> fields are demoninated in satoshis.
						</p>
						<p>
							The <em>peg</em> is empty unless price pegging feature used. <em>peg_value</em> measured in satoshis or cents (depending on the peg)
						</p>
						<strong>Sample JSON:</strong>
						<pre>
{
    "id": 14,
    "address": "1NVvfJeysGF7ZwT75aNzuY7oyYwZDMQnXF",
    "token": "LTBCOIN",
    "total": 100000000,
    "peg": "",
    "peg_value": 0
    "received": 0,
    "complete": false,
    "init_date": "2015-05-14 14:32:04",
    "complete_date": null,
    "tx_info": null,
    "reference": "0",
    "slot_id": "xiXyx5X2G3G3CIS1mg98"
}
						</pre>						
					</div>
				</li>				
				<li>
					<a class="btn" data-toggle="collapse" data-target="#ob3">Slot</a>
					<div id="ob3" class="collapse in">
						<strong>Fields:</strong>
						<ul>
							<li>public_id (string)</li>
							<li>tokens (array)</li>
							<li>webhook (string)</li>
							<li>min_conf (integer)</li>
							<li>forward_address (string)</li>
							<li>label (string)</li>
							<li>nickname (string)</li>
							<li>created_at (timestamp)</li>
							<li>updated_at (timestamp)</li>
						</ul>
						<p>
							A slot can be referenced be either its <em>public_id</em> or <em>nickname</em> field.
						</p>
						<strong>Sample JSON:</strong>
						<pre>
{
    "public_id": "znCFvFzeldWHF9BePxBv",
	"tokens": [
		"TOKENLY"
	],
    "webhook": "https://example.org/tokenslot-hook",
    "min_conf": 1,
    "forward_address": "1KthnhXAWmD6TuyjK3KbswVWvkuK3i2Keq",
    "label": "demo_hook",
    "nickname": "demo",
    "created_at": "2015-05-14 01:04:28",
    "updated_at": "2015-05-14 01:04:28"
}						
						</pre>
					</div>
				</li>
				<li>
					<a class="btn" data-toggle="collapse" data-target="#ob4">Transaction Info</a>
					<div id="ob4" class="collapse in">
						<strong>Fields:</strong>
						<ul>
							<li>sources (array)</li>
							<li>txid (string)</li>
							<li>amount (integer)</li>
							<li>confirmations (integer)</li>
						</ul>
						<p>
							<em>sources</em> is an array of bitcoin addresses involved in the transaction.
						</p>
						<p>
							<em>confirmations</em> are only tracked up to the <em>min_conf</em> field in the parent payment request's slot.
						</p>
						<strong>Sample JSON:</strong>
						<pre>
{
	"sources": [
		"1KthnhXAWmD6TuyjK3KbswVWvkuK3i2Keq"
	],
	"txid": "6755cc62874303303ae31a1303fd2b76c3db12dbb942abf053d334b1e051ac39",
	"amount": 100000000,
	"confirmations": 0
}
						</pre>							
					</div>
				</li>			
			</ul>
			<hr>
			<a name="contact"></a>
			<h3>Contact Us</h3>
			<p>
				Token Slot is currently in a private beta, to get involved and become one of our
				early use cases, please contact <a href="mailto:team@tokenly.com">team@tokenly.com</a>.
			</p>
			<p>
				For specific development inquiries, contact <a href="mailto:nick@tokenly.com">nick@tokenly.com</a>.
			</p>
        </div>

      </div>

      <footer class="footer">
        <p>&copy; Tokenly <?= date('Y') ?></p>
      </footer>

    </div> <!-- /container -->
    </div><!-- main-container -->

	<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
	<script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>  
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>    	
	<script type="text/javascript">
		function scrollToAnchor(aid){
			var aTag = $("a[name='"+ aid +"']");
			$('html,body').animate({scrollTop: aTag.offset().top - 110},'slow');
		}
		$('.nav a').click(function(e){
			if(!$(this).hasClass('no-anchor')){
				var thisAnchor = $(this).attr('href').replace('#', '');
				e.preventDefault();
				scrollToAnchor(thisAnchor);				
			}
		});		
	</script>
  </body>
</html>
