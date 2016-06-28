<img src="assets/images/lock_icon.png" width="57"/>
<h1 class="headline margin-top-20">Sign On</h1>
<div class="row">
	<div class="col-md-4"></div>
    <div class="col-md-4">
        <div class="margin-top-75 text-left"> 
        <?php print render($form['name']);?>				
        <?php print render($form['pass']);?>	
        </div>
        <p class="simple-link margin-bottom-0  loadLandingContent" id="landingCTABack2" style="color:#0077bb;cursor:pointer;">Request New Password</p>
	</div>
    <div class="col-md-4"></div>
</div>
<div class="row">
        <div class="col-md-2"></div>
        <div class="col-md-8">
        	<div class="row margin-top-30">
        		<div class="col-sm-6 col-padding-right-10">
            		<button type="button" class="btn btn-default btn-lg btn-block loadLandingContent" id="landingCTABack00">Cancel</button>
            	</div>
                <div class="col-sm-6 col-padding-left-10 mobile-margin-top-20">
                 <?php 
				   print drupal_render($form['form_build_id']);
                   print drupal_render($form['form_id']);
                   print drupal_render($form['actions']);
				 ?>
            	</div>
              </div>
        </div>
        <div class="col-md-2"></div>
</div>