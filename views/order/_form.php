<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\jui\AutoComplete;
use kartik\money\MaskMoney;
use kartik\widgets\Select2;
use kartik\widgets\SwitchInput;
use kartik\datetime\DateTimePicker;
use amilna\yes\models\PaymentSearch;
use amilna\yes\models\CouponSearch;

use yii\captcha\Captcha;

/* @var $this yii\web\View */
/* @var $model amilna\yes\models\Order */
/* @var $form yii\widgets\ActiveForm */

$module = Yii::$app->getModule("yes");

$listPayment = []+ArrayHelper::map(PaymentSearch::find()->select(["id","concat(terminal,' (',account,')') as terminal"])->andWhere("status = 1")->all(), 'id', 'terminal');
$payment = ($model->isNewRecord?$model->id['payment']:false);

$now = date('Y-m-d H:i:s');
$encoupon = CouponSearch::find()->where("isdel = 0 and status = 1 and time_from <= '".$now."' and time_to >= '".$now."'")->select(["id"])->one();
?>

<div class="order-form">

    <?php $form = ActiveForm::begin(); ?>
	
	<div id="ordertab" role="tabpanel">
		  <!-- Nav tabs -->
		  <ul class="nav nav-tabs nav-justified" role="tablist">
			<li role="presentation" class="active"><a href="#customer" aria-controls="customer" role="tab" data-toggle="tab"><?= Yii::t("app","Your Data") ?></a></li>
			<li role="presentation"><a href="#address" aria-controls="address" role="tab" data-toggle="tab"><?= Yii::t("app","Shipping") ?></a></li>
			<li role="presentation"><a href="#summary" aria-controls="summary" role="tab" data-toggle="tab"><?= Yii::t("app","Total") ?></a></li>
			<li role="presentation"><a href="#payment" aria-controls="payment" role="tab" data-toggle="tab"><?= Yii::t("app","Payment") ?></a></li>			
		  </ul>

		  <!-- Tab panes -->
		  <div class="tab-content panel">
			<div role="tabpanel" class="tab-pane panel-body active" id="customer">
				<div class="row">		
					<div class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">
						<h3 class="text-center"><?= Yii::t("app","Customer Information")?></h3>
						<div class="form-group">
						<?= Html::label(Yii::t("app","Name"))?>
						<?php 							
							$field = $form->field($model,"customer_id[name]");
							$field->template = "{input}";
							echo $field->widget(AutoComplete::classname(),[								
								'clientOptions' => [
									'source' => Yii::$app->urlManager->createUrl(["//yes/customer/search","format"=>"json","arraymap"=>"name"]),
								],
								'clientEvents' => [				
									'select' => 'function(event, ui) {												
													//console.log(event,ui,"tes");							
												}',
								],
								'options'=>[
									'class'=>'form-control required','maxlength' => 255,				
									'placeholder' => Yii::t('app','Your fullname...')
								]
							]) 
						?>
						</div>
						<div class="form-group">
						<?= Html::label(Yii::t("app","Email"))?>	
						<?php	
							$field = $form->field($model,"complete_reference[email]");
							$field->template = "{input}";
							echo $field->textInput(["class"=>"form-control","placeholder"=>Yii::t("app","Email address")]);						
						?>	 
						</div> 
						<div class="form-group">
						<?= Html::label(Yii::t("app","Phones"))?>							
						<?php 
							$field = $form->field($model,"customer_id[phones]");
							$field->template = "{input}";
							echo $field->textInput(["class"=>"form-control","placeholder"=>Yii::t("app","Please include country code and area code, ex: 62-21-740xxxx...")]);						
							/*
							echo $field->widget(Select2::classname(), [
								'options' => [
									'placeholder' => Yii::t('app','Please include country code and area code, ex: 62-21-740xxxx...'),
								],
								'pluginOptions' => [
									'tags' => [],
								],
							]);
							*/ 
						?>		
						</div> 
						<hr>
						<a onclick="$('#ordertab a[href=\'#address\']').tab('show')" class="btn btn-success btn-tab pull-right" ><?= Yii::t("app","Next") ?></a>
					</div>
				</div>					
			</div>	
			<div role="tabpanel" class="tab-pane panel-body" id="address">
				<div class="row">		
					<div class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">
						<h3 class="text-center"><?= Yii::t("app","Shipping Adddress & Method")?></h3>
						<div class="well addresses">
							<h4><?= Yii::t('app','Addresses') ?> <small class="pull-right list-address"></small></h4>
							<br>
							<?php	
								$field = $form->field($model,"customer_id[address]");
								$field->template = "{input}";
								echo $field->textArea(["class"=>"form-control","placeholder"=>Yii::t("app","Shipping address")]);						
							?>	 
						</div>
						<div class="form-group">
						<?= Html::label(Yii::t("app","City"))?>
						<?php 
							$field = $form->field($model,"data[city]");
							$field->template = "{input}";
							echo $field->widget(AutoComplete::classname(),[								
								'clientOptions' => [
									'source' => Yii::$app->urlManager->createUrl(["//yes/shipping/index","ShippingSearch[status]"=>"> 0","format"=>"json","arraymap"=>"label:cityArea,value:Obj"]),
								],
								'clientEvents' => [										
									'focus' => 'function(event, ui) {													
													//console.log(JSON.parse(ui.item.value));	
													renderShip(ui.item.value);
												}',
									'close' => 'function(event, ui) {													
													var val = $("#order-data-city").val();
													try{
														val = JSON.parse(val);													
														$("#order-data-city").val(val.city+" ("+val.area+")");
													}
													catch (e) 
													{
														//console.log("hapus");	
														resetShip();
													}																										
												}',
									'change'=> 'function(event, ui) {						
													var cek = $(".radio-shipping-cost").html();
													var berat = parseFloat($("#shopcart-box h4").attr("data-weight"));	
													if (cek == "" && berat > 0)													
													{																										
														$("#order-data-city").val("");
													}																										
												}',			
								],
								'options'=>[
									'class'=>'form-control required','maxlength' => 255,				
									'placeholder' => Yii::t('app','City where address located...')
								]
							]); 
						?>
						</div>
						<div class="radio-shipping-cost"></div>
						<hr>
						<a onclick="$('#ordertab a[href=\'#customer\']').tab('show')" class="btn btn-warning btn-tab pull-left" ><?= Yii::t("app","Previous") ?></a>
						<a onclick="$('#ordertab a[href=\'#summary\']').tab('show')" class="btn btn-success btn-tab pull-right" ><?= Yii::t("app","Next") ?></a>
					</div>
				</div>					
			</div>
			<div role="tabpanel" class="tab-pane panel-body" id="summary">
				<div class="row">		
					<div class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">
						<h3 class="text-center"><?= Yii::t("app","Summary & Total")?></h3>
						<div class="well shopcart-box">
							<h4><?= Yii::t("app","Shopping Cart")?> <span class="shopcart-badge badge"></span> <small class="pull-right"></small></h4>
							<table class="table table-striped table-bordered">
							</table>								
						</div>
						<div class="well">
						<div class="order-shippingcost-label"></div>
						<?= Html::hiddenInput('Order[data][shippingcost]',0,["class"=>"form-control order-data-shippingcost"]); ?>
						<br>
						<div class="order-vat-label"></div>						
						<?= Html::hiddenInput('Order[data][vat]',0,["class"=>"form-control order-data-vat"]); ?>
						<br>
						<?php
							if ($encoupon)
							{
								echo '<h4>'.Yii::t('app','Coupon').'</h4>';
								echo '<div class="row">';
								echo '<div class="col-xs-6">';
								$field = $form->field($model,"complete_reference[coupon]");
								$field->template = "{input}";
								echo $field->textInput(["class"=>"form-control","class"=>"form-control","placeholder"=>Yii::t("app","Coupon Code")]);														
								echo '</div>';
								echo '<div class="col-xs-6"><div class="order-coupon-label"></div></div>';
								echo '</div>';
							}						
						?>						
						<?= Html::hiddenInput('Order[data][coupon]',0,["class"=>"form-control order-data-coupon"]); ?>
						<div class="order-grandtotal-label"></div>
						<?= Html::hiddenInput('Order[total]',0,["class"=>"form-control order-total"]); ?>
						</div>
						<hr>
						<a onclick="$('#ordertab a[href=\'#address\']').tab('show')" class="btn btn-warning btn-tab pull-left" ><?= Yii::t("app","Previous") ?></a>
						<a onclick="$('#ordertab a[href=\'#payment\']').tab('show')" class="btn btn-success btn-tab pull-right" ><?= Yii::t("app","Next") ?></a>
					</div>
				</div>					
			</div>
			<div role="tabpanel" class="tab-pane panel-body" id="payment">				
				<div class="row">		
					<div class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">
						<h3 class="text-center"><?= Yii::t("app","Payment & Submit")?></h3>
						<div class="form-group">
							<label for="Order[data][payment]"><?= Yii::t('app','Payment method') ?></label>
							<?php
							
								$field = $form->field($model,"data[payment]");
								$field->template = "{input}";
								echo $field->widget(Select2::classname(),[								
									'data' => $listPayment,								
									'options' => [
										'placeholder' => Yii::t('app','Select payment method ...'), 
										'multiple' => false,
										'template'=>'{input}'
									],
								]);
							?>
						</div>

						<div class="form-group">
						<?= Html::label(Yii::t("app","Additional Notes"))?>	
						<?php 
							
							$data = $model->data;
							if (!isset($data["note"]))
							{
								$model->data = ["note"=>"-"];
							}							
							
							$field = $form->field($model,"data[note]");
							$field->template = "{input}";
							echo $field->textArea(["class"=>"form-control required","placeholder"=>Yii::t("app","Notes for us / special request")]);
							
							$model->data = $data;
						?>	
						</div>
						
						<?php
							if ($model->captchaRequired)
							{
						?>
						
						<div class="well">							
							<div class="row">									
						<?php						
						echo $form->field($model,"captcha")->widget(Captcha::classname(),[
							'captchaAction' => ['/yes/order/captcha'],							
							'imageOptions'=>['class'=>'col-sm-4 col-xs-6','style'=>'margin:20px -5px 20px -5px;max-height:80px;cursor:pointer;'],
							'options'=>['class'=>'col-sm-8 col-xs-6'],							
						]);
						?>							
							</div>
						</div>
						
						<?php
							}
						?>
												
						<hr>
						<a onclick="$('#ordertab a[href=\'#summary\']').tab('show')" class="btn btn-warning btn-tab pull-left" ><?= Yii::t("app","Previous") ?></a>
						<div class="form-group">
							<?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-primary pull-right' : 'btn btn-primary pull-right']) ?>
						</div>			
						
					</div>
				</div>	
			</div>
		</div>
	</div>	
	
    <?php ActiveForm::end(); ?>

</div>

<?php

$this->render('@amilna/yes/views/product/_script_add',['model'=>$model]);
$this->render('@amilna/yes/views/order/_script_ship',['model'=>$model]);
$this->render('@amilna/yes/views/order/_script_coupon',['model'=>$model]);
$this->render('@amilna/yes/views/order/_script_customer',['model'=>$model]);
$this->render('@amilna/yes/views/order/_script_load',['model'=>$model]);
