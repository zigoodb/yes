<?php

namespace amilna\yes\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Html;

use amilna\yes\models\Shipping;


class Helpers extends Component
{
	public static function importCsv($f,$data = false,$dn = false)
    {				
		$session = Yii::$app->session;		
		if (!$data)
		{
			$header = false;
			$data = [];
			while ($row = fgetcsv($f)) {
							
				if(!$header)
				{
					foreach($row as $r)
					{
						$header[] = $r;	
					}
				}	
				else
				{						
					$d = [];
					foreach($row as $i=>$r)
					{
						$h = $header[$i];
						$d[$h] = $r;	
					}	
					$data[]	= $d;		
				}	
			}
							
			$session["yes-shipping-f"] = $data;				
		}				
		
		$dno = $dn?$dn:$session["yes-shipping-dn"];			
		
		$transaction = Yii::$app->db->beginTransaction();
		$res = true;
		$error = [];
		try {											
			
			if ($dno >= 0)
			{
				//foreach ($data as $d)
				for ($dni = 0;$dni< 100;$dni++)
				{
					$dn = $dno+$dni;
					if (isset($data[$dn]))
					{							
						$d = $data[$dn];			
						
						if (isset($d["cost"]) && isset($d["provider"]) && isset($d["remarks"]) && isset($d["city"]) && isset($d["area"]) && isset($d["code"]))
						{
							if (floatval($d["cost"]) > 0)
							{				
								$model = Shipping::find()->where('code = :code OR (city = :city AND area = :area)',[':code'=>$d["code"],':city'=>$d["city"],':area'=>$d["area"]])->one();
								if (!$model)
								{
									$model = new Shipping();
								}
								$old = empty($model->data)?[]:json_decode($model->data,true);				
								$exists = false;
								$n = 1;
								$new = [];
								foreach ($old as $o)
								{
									if ($o["provider"] == $d["provider"])
									{
										$exists = true;	
										$o["cost"] = $d["cost"];
										$o["remarks"] = $d["remarks"];
									}
									$new[] = $o;
									$n += 1;	
								}
												 										
								if (!$exists)
								{
									$new[$n.""] = ["provider"=>$d["provider"],"cost"=>$d["cost"],"remarks"=>$d["remarks"]];
								}
								
								$model->code = $d["code"];
								$model->city = $d["city"];
								$model->area = $d["area"];
								$model->data = json_encode($new);
								$model->isdel = 0;
								$model->status = 1;								
								
								if (!$model->save())
								{
									$res = $res?false:$res;	
									$error[] = [$model->attributes,$model->getErrors()];
								}									
								
							}
						}
					}
				}
			}
			
			if ($dn >= count($data) || $dn < 0)
			{				
				unset($session["yes-shipping-f"]);
				unset($session["yes-shipping-dn"]);
				$data = [];
			}
			
			
			if ($res)
			{
				$transaction->commit();
			}
			else
			{
				$transaction->rollBack();	
			}
		} catch (Exception $e) {
			$transaction->rollBack();
		}				
		
		$session["yes-shipping-dn"] = $dn;		
		
		if ($res)
		{
			return ["status"=>$res,"count"=>count($data),"n"=>$dn];
		}
		else
		{
			return ["status"=>$res,"count"=>count($data),"n"=>$dn,"error"=>$error];
		}
	}
        
}	
