
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

    <div class="container">
      <div class="header clearfix">
        <nav>
          <ul class="nav nav-pills pull-right">
            <li role="presentation"><a href="#intro">Intro</a></li>
            <li role="presentation"><a href="#usage">Usage</a></li>
            <li role="presentation"><a href="#api">API</a></li>
            <li role="presentation"><a href="#contact">Contact</a></li>
            <li role="presentation"><a href="https://github.com/tokenly/token-slot" target="_blank"><i class="fa fa-github-alt"></i> Github</a></li>
          </ul>
        </nav>
        <h3 class="text-muted"><i class="fa fa-btc"></i> Tokenly</h3>
      </div>

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
				Used in combination with <a href="https://github.com/tokenly/swapbot" target="_blank">SwapBot</a>, a
				fully tokenized redeemable product ecosystem can emerge. [insert an interesting use case here]
			</p>
			<hr>
			<a name="usage"></a>
			<h2>Basic Usage</h2>
			<p>
				<a href="https://letstalkbitcoin.com/tokenslot-demo" target="_blank">Click here</a> to view a basic front-end tech demo.
			</p>
			<p>
				[add in some basic code samples here]
			</p>
			<hr>
			<a name="api"></a>
			<h2>API Reference</h2>
			<h3>Methods</h3>
			<p><strong>/api/v1/...</strong></p>
			<ul class="api-list">
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api1">payments/request/{slot_id} [GET]</a>
					<div id="api1">
						<p>
							Creates a new payment request and deposit address for the customer.
						</p>
						<strong>Parameters:</strong>
						<ul>
							<li>total (integer) - optional</li>
							<li>reference (string) - optional</li>
						</ul>
						<strong>Returns:</strong>
						<ul>
							<li>payment_id (integer)</li>
							<li>address (string)</li>
						</ul>						
						<p>
							The total field should be the total amount of the order, in satoshis.
							If total is set to 0, it assumes a "pay what you want" type situation.
						</p>
						<p>
							The reference field can be used to store a custom identifier
							for the payment request, and should be unique per payment.
						</p>

					</div>
				</li>
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api2">payments/{payment_id} [GET]</a>
					<div id="api2">
						
					</div>
				</li>			
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api3">payments/{payment_id}/cancel [POST]</a>
					<div id="api3">
						
					</div>
				</li>
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api4">payments/all [GET]</a>
					<div id="api4">
						
					</div>
				</li>								
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api5">slots [GET]</a>
					<div id="api5">
						
					</div>
				</li>	
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api6">slots [POST]</a>
					<div id="api6">
						
					</div>
				</li>							
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api7">slots/{slot_id} [GET]</a>
					<div id="api7">
						
					</div>
				</li>
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api8">slots/{slot_id} [PATCH]</a>
					<div id="api8">
						
					</div>
				</li>		
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api9">slots/{slot_id} [DELETE]</a>
					<div id="api9">
						
					</div>
				</li>					
				<li>
					<a class="btn" data-toggle="collapse" data-target="#api10">slots/{slot_id}/payments [GET]</a>
					<div id="api10">
						
					</div>
				</li>		
			</ul>
				

			<hr>
			<a name="contact"></a>
			<h2>Contact Us</h2>
			<p>
				Token Slot is currently in a private beta, to get involved and become one of our
				early use cases, please contact <a href="mailto:team@tokenly.com">team@tokenly.com</a>.
			</p>
        </div>

      </div>

      <footer class="footer">
        <p>&copy; Tokenly <?= date('Y') ?></p>
      </footer>

    </div> <!-- /container -->

	<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
	<script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>  
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>    	
 
  </body>
</html>
