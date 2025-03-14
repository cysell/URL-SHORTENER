<?php defined("APP") or die() ?>
<section id="plan">
  <div class="container">
    <?php echo Main::message() ?>
    <div class="text-center">
      <h1><?php echo e("Choose your premium plan") ?></h1>
      <br>
      <div class="toggle-container cf">
        <div class="switch-toggles">
          <div class="monthly"><?php echo e("Monthly") ?></div>
          <div class="yearly"><?php echo e("Yearly") ?></div>
        </div>
      </div>      
    </div>    
    <div id="price_tables">
      <div class="monthly cf">
        <?php foreach ($free as $plan): ?>
          <div class="price-table">
            <div class="table-inner text-center">              
              <h3><?php echo e($plan["name"]) ?></h3>
              <div class="phrase"><?php echo e($plan["description"]) ?></div>
              <span class="price"><?php echo e("Free") ?></span>
              <ul class="feature-list">
                <li><?php echo e("Basic Features") ?></li>
                <li><?php echo $plan["urls"]== "0" ? e("Unlimited") : $plan["urls"] ?> <?php echo e("URLs allowed") ?></li>
                <?php if ($plan["permission"]->geo->enabled): ?>
                  <li><?php echo e("Geotargeting"); ?></li>
                <?php endif ?>
                <?php if ($plan["permission"]->device->enabled): ?>
                  <li><?php echo e("Device Targeting"); ?></li>
                <?php endif ?>                
                <?php if ($plan["permission"]->splash->enabled): ?>
                  <li><?php echo ($plan["permission"]->splash->count == "0" ? e("Unlimited") : $plan["permission"]->splash->count)." ".e("Custom Splash Pages"); ?></li>
                <?php endif ?>
                <?php if ($plan["permission"]->overlay->enabled): ?>
                  <li><?php echo ($plan["permission"]->overlay->count == "0" ? e("Unlimited") : $plan["permission"]->overlay->count)." ".e("Custom Overlay Pages"); ?></li>
                <?php endif ?>         
                <?php if ($plan["permission"]->pixels->enabled): ?>
                  <li><?php echo ($plan["permission"]->pixels->count == "0" ? e("Unlimited") : $plan["permission"]->pixels->count)." ".e("Event Tracking"); ?></li>
                <?php endif ?>                 
                <?php if ($plan["permission"]->domain->enabled): ?>
                  <li><?php echo ($plan["permission"]->domain->count == "0" ? e("Unlimited") : $plan["permission"]->domain->count)." ".e("Custom Domains"); ?></li>
                <?php endif ?>   
                <?php if ($plan["permission"]->export->enabled): ?>
                  <li><?php echo e("Export Data") ?></li>        
                <?php endif ?>                 
                <?php if ($plan["permission"]->api->enabled): ?>
                  <li><?php echo e("Developer API"); ?></li>
                <?php endif ?>                               
                <li><?php echo e("Limited URL Customization") ?></li>                
                <li><?php echo e("Advertisement") ?></li>          
                <li>&nbsp;</li>
              </ul>
              <?php if($this->logged()): ?>
                <?php if (!$this->pro()): ?>
                  <a class="btn btn-primary btn-round"><?php echo e("Current Plan") ?></a> 
                <?php endif ?>
              <?php else: ?>
                <a href="<?php echo Main::href("user/register") ?>" class="btn btn-secondary btn-round"><?php echo e("Get Started") ?></a> 
              <?php endif ?>               
            </div>
          </div>          
        <?php endforeach ?>
        <?php foreach ($monthly as $plan): ?>
          <div class="price-table highlighted">
            <div class="table-inner text-center">
              <h3><?php echo e($plan["name"]) ?></h3>
              <div class="phrase"><?php echo e($plan["description"]) ?></div>
              <span class="price"><?php echo Main::currency($this->config["currency"],$plan["price"]) ?></strong><small>/mo</small></span>
              <ul class="feature-list">
                <li><?php echo e("Premium Features") ?></li>
                <li><?php echo $plan["urls"]== "0" ? e("Unlimited") : $plan["urls"] ?> <?php echo e("URLs allowed") ?></li>
                <?php if ($plan["permission"]->geo->enabled): ?>
                  <li><?php echo e("Geotargeting"); ?></li>
                <?php endif ?>
                <?php if ($plan["permission"]->device->enabled): ?>
                  <li><?php echo e("Device Targeting"); ?></li>
                <?php endif ?>                
                <?php if ($plan["permission"]->splash->enabled): ?>
                  <li><?php echo ($plan["permission"]->splash->count == "0" ? e("Unlimited") : $plan["permission"]->splash->count)." ".e("Custom Splash Pages"); ?></li>
                <?php endif ?>
                <?php if ($plan["permission"]->overlay->enabled): ?>
                  <li><?php echo ($plan["permission"]->overlay->count == "0" ? e("Unlimited") : $plan["permission"]->overlay->count)." ".e("Custom Overlay Pages"); ?></li>
                <?php endif ?>         
                <?php if ($plan["permission"]->pixels->enabled): ?>
                  <li><?php echo ($plan["permission"]->pixels->count == "0" ? e("Unlimited") : $plan["permission"]->pixels->count)." ".e("Event Tracking"); ?></li>
                <?php endif ?>                 
                <?php if ($plan["permission"]->domain->enabled): ?>
                  <li><?php echo ($plan["permission"]->domain->count == "0" ? e("Unlimited") : $plan["permission"]->domain->count)." ".e("Custom Domains"); ?></li>
                <?php endif ?>   
                <?php if ($plan["permission"]->export->enabled): ?>
                  <li><?php echo e("Export Data") ?></li>        
                <?php endif ?>                 
                <?php if ($plan["permission"]->api->enabled): ?>
                  <li><?php echo e("Developer API"); ?></li>
                <?php endif ?>                                                  
                <li><?php echo e("URL Customization") ?></li>                              
                <li><?php echo e("No Advertisements") ?></li>
                <?php if (!empty($plan["permission"]->custom)): ?>
                  <li><?php echo e($plan["permission"]->custom); ?></li>
                <?php endif ?>  
              </ul>
              <?php if ($this->logged() && $this->pro() && $this->user->planid == $plan["id"]): ?>
                  <a class="btn btn-primary btn-round"><?php echo e("Current Plan") ?></a> 
              <?php else: ?>
                <a href="<?php echo Main::href("upgrade/monthly/{$plan["id"]}") ?>" class="btn btn-secondary btn-round"><?php echo e("Go Pro") ?></a>     
              <?php endif ?>                            
            </div>
          </div>          
        <?php endforeach ?>
      </div>

      <div class="yearly cf">
        <?php foreach ($free as $plan): ?>
          <div class="price-table">
            <div class="table-inner text-center">
              <h3><?php echo e($plan["name"]) ?></h3>
              <div class="phrase"><?php echo e($plan["description"]) ?></div>
              <span class="price"><?php echo e("Free") ?></span>
              <ul class="feature-list">
                <li><?php echo e("Basic Features") ?></li>
                <li><?php echo $plan["urls"]== "0" ? e("Unlimited") : $plan["urls"] ?> <?php echo e("URLs allowed") ?></li>
                <?php if ($plan["permission"]->geo->enabled): ?>
                  <li><?php echo e("Geotargeting"); ?></li>
                <?php endif ?>
                <?php if ($plan["permission"]->device->enabled): ?>
                  <li><?php echo e("Device Targeting"); ?></li>
                <?php endif ?>                
                <?php if ($plan["permission"]->splash->enabled): ?>
                  <li><?php echo ($plan["permission"]->splash->count == "0" ? e("Unlimited") : $plan["permission"]->splash->count)." ".e("Custom Splash Pages"); ?></li>
                <?php endif ?>
                <?php if ($plan["permission"]->overlay->enabled): ?>
                  <li><?php echo ($plan["permission"]->overlay->count == "0" ? e("Unlimited") : $plan["permission"]->overlay->count)." ".e("Custom Overlay Pages"); ?></li>
                <?php endif ?>         
                <?php if ($plan["permission"]->pixels->enabled): ?>
                  <li><?php echo ($plan["permission"]->pixels->count == "0" ? e("Unlimited") : $plan["permission"]->pixels->count)." ".e("Event Tracking"); ?></li>
                <?php endif ?>                 
                <?php if ($plan["permission"]->domain->enabled): ?>
                  <li><?php echo ($plan["permission"]->domain->count == "0" ? e("Unlimited") : $plan["permission"]->domain->count)." ".e("Custom Domains"); ?></li>
                <?php endif ?>   
                <?php if ($plan["permission"]->export->enabled): ?>
                  <li><?php echo e("Export Data") ?></li>        
                <?php endif ?>                 
                <?php if ($plan["permission"]->api->enabled): ?>
                  <li><?php echo e("Developer API"); ?></li>
                <?php endif ?>                               
                <li><?php echo e("Limited URL Customization") ?></li>                
                <li><?php echo e("Advertisement") ?></li>          
                <li>&nbsp;</li>
              </ul>
              <?php if($this->logged()): ?>
                <?php if (!$this->pro()): ?>
                  <a class="btn btn-primary btn-round"><?php echo e("Current Plan") ?></a> 
                <?php endif ?>
              <?php else: ?>
                <a href="<?php echo Main::href("user/register") ?>" class="btn btn-secondary btn-round"><?php echo e("Get Started") ?></a> 
              <?php endif ?>               
            </div>
          </div>          
        <?php endforeach ?>
        <?php foreach ($yearly as $plan): ?>
          <div class="price-table highlighted">
            
            <?php if ($plan["discount"] > 1): ?>
              <div class="corner-ribbon top-left"><?php echo e("Save")." {$plan["discount"]}" ?>%</div>
            <?php endif ?>

            <div class="table-inner text-center">
              <h3><?php echo e($plan["name"]) ?></h3>
              <div class="phrase"><?php echo e($plan["description"]) ?></div>
              <span class="price"><?php echo Main::currency($this->config["currency"],round($plan["price"]/12,2)) ?></strong><small>/mo</small><small class="billed"><?php echo e("Billed") ?> <?php echo Main::currency($this->config["currency"],$plan["price"]) ?></small></span>          
              <ul class="feature-list">
                <li><?php echo e("Premium Features") ?></li>
                <li><?php echo $plan["urls"]== "0" ? e("Unlimited") : $plan["urls"] ?> <?php echo e("URLs allowed") ?></li>
                <?php if ($plan["permission"]->geo->enabled): ?>
                  <li><?php echo e("Geotargeting"); ?></li>
                <?php endif ?>
                <?php if ($plan["permission"]->device->enabled): ?>
                  <li><?php echo e("Device Targeting"); ?></li>
                <?php endif ?>                
                <?php if ($plan["permission"]->splash->enabled): ?>
                  <li><?php echo ($plan["permission"]->splash->count == "0" ? e("Unlimited") : $plan["permission"]->splash->count)." ".e("Custom Splash Pages"); ?></li>
                <?php endif ?>
                <?php if ($plan["permission"]->overlay->enabled): ?>
                  <li><?php echo ($plan["permission"]->overlay->count == "0" ? e("Unlimited") : $plan["permission"]->overlay->count)." ".e("Custom Overlay Pages"); ?></li>
                <?php endif ?>         
                <?php if ($plan["permission"]->pixels->enabled): ?>
                  <li><?php echo ($plan["permission"]->pixels->count == "0" ? e("Unlimited") : $plan["permission"]->pixels->count)." ".e("Event Tracking"); ?></li>
                <?php endif ?>                 
                <?php if ($plan["permission"]->domain->enabled): ?>
                  <li><?php echo ($plan["permission"]->domain->count == "0" ? e("Unlimited") : $plan["permission"]->domain->count)." ".e("Custom Domains"); ?></li>
                <?php endif ?>   
                <?php if ($plan["permission"]->export->enabled): ?>
                  <li><?php echo e("Export Data") ?></li>        
                <?php endif ?>                 
                <?php if ($plan["permission"]->api->enabled): ?>
                  <li><?php echo e("Developer API"); ?></li>
                <?php endif ?>                                                  
                <li><?php echo e("URL Customization") ?></li>                              
                <li><?php echo e("No Advertisements") ?></li>
                <?php if (!empty($plan["permission"]->custom)): ?>
                  <li><?php echo e($plan["permission"]->custom); ?></li>
                <?php endif ?>  
              </ul>
              <?php if ($this->logged() && $this->pro() && $this->user->planid == $plan["id"]): ?>
                  <a class="btn btn-primary btn-round"><?php echo e("Current Plan") ?></a> 
              <?php else: ?>
                <a href="<?php echo Main::href("upgrade/yearly/{$plan["id"]}") ?>" class="btn btn-secondary btn-round"><?php echo e("Go Pro") ?></a>     
              <?php endif ?>                         
            </div>
          </div>          
        <?php endforeach ?>
      </div>
    </div>
  </div>
</section>
<hr>
<section>
  <div class="container">
    <div class="featurette">
      <h3 class="text-center featureH"><?php echo e("Premium Features. All Yours.") ?></h3>
      <div class="row">
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-globe"></i>
          <h3><?php echo e("Target Customers") ?></h3>
          <p><?php echo e("Target your users based on their location and device and redirect them to specialized pages to increase your conversion.") ?></p>
        </div>    
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-star"></i>
          <h3><?php echo e("Custom Landing Page") ?></h3>
          <p><?php echo e("Create a custom landing page to promote your product or service on forefront and engage the user in your marketing campaign.") ?></p>
        </div>      
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-asterisk"></i>
          <h3><?php echo e("Overlays") ?></h3>
          <p><?php echo e("Use our overlay tool to display unobtrusive notifications on the target website. A perfect way to send a message to your customers or run a promotion campaign.") ?></p>
        </div>
      </div>    
      <br> 
      <div class="row">
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-th"></i>
          <h3><?php echo e("Event Tracking") ?></h3>
          <p><?php echo e("Add your custom pixel from providers such as Facebook and track events right when they are happening.") ?></p>
        </div>        
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-glass"></i>
          <h3><?php echo e("Premium Aliases") ?></h3>
          <p><?php echo e("As a premium membership, you will be able to choose a premium alias for your links from our list of reserved aliases.") ?></p>
        </div>     
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-cloud"></i>
          <h3><?php echo e("Robust API") ?></h3>
          <p><?php echo e("Use our powerful API to build custom applications or extend your own application with our powerful tools.") ?></p>
        </div>         
      </div>
    </div>    
  </div>       
</section>