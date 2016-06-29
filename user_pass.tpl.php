<?php
//print print_r($form, TRUE); ?>

<img src="assets/images/lock_icon.png" width="57" class="margin-top-85"/>
<h1 class="headline margin-top-20">Request New Password</h1>
<div class="row">
	<div class="col-md-4"></div>
    <div class="col-md-4">
        <div class="margin-top-75 text-left">
           <?php print render($form['name']);?>	            
			
        </div>
	</div>
    <div class="col-md-4"></div>
</div>
<div class="row">
      <div class="col-md-2"></div>
      <div class="col-md-8">
      	<div class="row margin-top-70">
      		<div class="col-sm-6 col-padding-right-10">
          		<button type="button" class="btn btn-default btn-lg btn-block loadLandingContent" id="landingCTABack0">Cancel</button>
          	</div>
              <div class="col-sm-6 col-padding-left-10 mobile-margin-top-20">
          		<?php 
				   print drupal_render($form['form_build_id']);
                   print drupal_render($form['form_id']);
                   print drupal_render($form['actions']);
				 ?>
				
				<button type="button" class="btn btn-primary btn-lg btn-block loadLandingContent" id="landingCTABack00000">Request New Password</button>
          	</div>
            </div>
      </div>
      <div class="col-md-2"></div>
  </div>
<div class="row">
	<div class="col-md-12">
    	<a href="" class="landingContent" id="landingCTABack000"><p class="simple-link margin-bottom-40">Back to Sign On</p></a>
    </div>
</div>