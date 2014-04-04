<?php
	function newpost_state($curl, $dec_num) 
	{ 
		curl_setopt($curl, CURLOPT_URL, "http://novaposhta.ua/frontend/tracking/ru?en=".$dec_num); 

		$matches = array();
		$html = curl_exec($curl);

		$output = str_replace(array("\r\n", "\r"), "\n", $html);
		$lines = explode("\n", $output);
		$new_lines = array();

		foreach ($lines as $i => $line) {
			if(!empty($line))
				$new_lines[] = trim($line);
		}
		$html = implode($new_lines);

		return $html;
	}

	$cargo_arrive_backshipment_template = '~<td class="tracking">Маршрут груза<\/td><td>(?<direct>.*?)</td></tr><tr><td class="tracking">Текущее местоположение</td><td>(?<status>Отправление прибыло). Приглашаем получить по адресу: <a href="/map/warehouse/id/(?<warehouse>\d*?)" target="__blank">(?<address>.*?)</a>.*?Напоминаем, что через (?<days_keep>\d+?) рабочих дней от даты прибытия \((?<arrival_date>\d\d\.\d\d\.\d\d\d\d)\) будут начисляться дополнительные средства за хранение.</td></tr><tr><td class="tracking">Обратная доставка</td><td>(?<status_back>Заказана услуга обратной доставки).</td></tr><tr><td class="tracking">Информация о оплате</td>.*?Сумма .*? - (?<cash_amount>.*?)</td>~';
	
	$cargo_arrive_template = '~<td class="tracking">Маршрут груза<\/td><td>(?<direct>.*?)</td></tr><tr><td class="tracking">Текущее местоположение</td><td>(?<status>Отправление прибыло). Приглашаем получить по адресу: <a href="/map/warehouse/id/(?<warehouse>\d*?)" target="__blank">(?<address>.*?)</a>.*?Напоминаем, что через (?<days_keep>\d+?) рабочих дней от даты прибытия \((?<arrival_date>\d\d\.\d\d\.\d\d\d\d)\) будут начисляться дополнительные средства за хранение.</td>~';
	
	$cargo_received_backshipment_template = '~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr><tr><td class="tracking">Текущее местоположение</td><td>(?<status>Отправление получено) (?<received_date>\d\d\.\d\d\.\d\d\d\d)( по адресу: <a href="/map/warehouse/id/)?(?<warehouse>\d*?)?(" target="__blank">)?(?<address>.*?)?(</a></td>)?.*?<td>(?<status_back>Обратную доставку отправлено).*?<form.*?id="(?<id_back>.*?)"~';
	
	$cargo_received_template = '~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr><tr><td class="tracking">Текущее местоположение</td><td>(?<status>Отправление получено) (?<received_date>\d\d\.\d\d\.\d\d\d\d)( по адресу: <a href="/map/warehouse/id/)?(?<warehouse>\d*?)?(" target="__blank">)?(?<address>.*?)?(</a></td>)?~';
	
	$cargo_reject_return_template = '~<td>(?<status>Получатель отказался от получения отправления).*?<form.*?id="(?<id_back>.*?)">~';
	
	$cargo_reject_template = '~<td>(?<status>Получатель отказался от получения отправления)~';
	
	$cargo_payed_transfer_template = '~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr><tr><td class="tracking">Дата прибытия</td><td>(?<transfer_date>\d\d\.\d\d\.\d\d\d\d).*?Информация о оплате</td><td>(?<status_back>Денежный перевод отправлено)</td>~';
	
	$cargo_resend_template = '~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr><tr><td class="tracking">Текущее местоположение</td><td>(?<status>Отправление переадресовано).*?<form.*?id="(?<id_new>.*?)">~';
	
	$cargo_removed_template = '~(?<status>Прекращено хранение отправления)~';
	
	$cargo_ontheway_template = '~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr><tr><td class="tracking">(?<status>Дата прибытия)</td><td>(?<arrival_date>\d\d.\d\d.\d\d\d\d?)</td>.*<td class="tracking">Адрес доставки</td>.*?(?<address>[Отделение|Заказано].*?)<~';
	
	$cargo_end_free_template = '~Закончился бесплатный срок хранения груза~';
	
	$cargo_end_template = '~Ваш груз поставлен на удаление~';
	$cargo_end_template2 = '~Истек срок хранения отправления~';
	
	$cargo_declarated_template = '~Cоздана электронная.*стадии обработки~';
		
	
	//-------------------------------------------------------------------------------------------------------------------------------
	
	$runtime = microtime(true);
	require("config.php"); 
	
	$query = "SELECT orders.*, item.day_back as day_back FROM orders LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id WHERE NOT newpost_id IS NULL AND newpost_id != '' AND newpost_id != '0' AND (:order_id IS NULL OR id = :order_id) AND (orders.status_step1 = 0 OR orders.status_step1 > 50) AND (orders.status_step2 = 0 OR orders.status_step2 > 50) AND (orders.status_step3 = 0 OR orders.status_step3 > 50)"; 
	$query_params = array(':order_id' => $_GET['id']); 
	 
	try{	$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); } 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	
	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_TIMEOUT, 20); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			
	$orders_count = 0;
	while($order = $stmt->fetch()) {
		$np_reply = newpost_state($curl, trim($order['newpost_id']));
		$newpost_status = $np_reply;
		
		$matches = array();
		preg_match($cargo_declarated_template,$np_reply,$matches);
		if(!empty($matches)) { $matches['activity']="Оформлена декларация"; $matches['msg']='Оформлена декларация'; $matches['status_step2'] = 250; $matches['from_status2'] = array(200); 
		} else {
			preg_match($cargo_arrive_backshipment_template,$np_reply,$matches);
			if(!empty($matches)) { 
					$at_adr_date = new DateTime($matches['arrival_date']);
					$now_date = new DateTime('now');
					$interval = $now_date->diff($at_adr_date);
					$interval = intval($interval->format('%R%d'));
					$matches['interval'] = $interval;
					$matches['msg'] = '<b>Прибыл!</b><br/>'.$matches['arrival_date'].'<br/>Прошло дней: '.(-$interval);
					if ($interval == -2) {
						$matches['status_step2'] = 207; $matches['from_status2'] = array(200,250,202,204);
						$matches['activity']="Груз 2й день на складе получателя"; 
					} else if ($interval == -3) {
						$matches['status_step2'] = 206; $matches['from_status2'] = array(200,250,202,204,207);
						$matches['activity']="Груз 3й день на складе получателя"; 
						$matches['alert_at']="NOW()"; 
					} else if ($interval == -4) {
						$matches['status_step2'] = 223; $matches['from_status2'] = array(200,250,202,204,207,206);
						$matches['activity']="Груз 4й день на складе получателя"; 
					} else if ($interval < -4 and $interval > -1*intval($order['day_back'])) {
						$matches['status_step2'] = 208; $matches['from_status2'] = array(200,250,202,204,207,206,223);
						$matches['activity']="Груз долго лежит на складе получателя"; 
						$matches['alert_at']="NOW()";
					}
					else if ($interval <= -1*intval($order['day_back'])) {
						$matches['status_step2'] = 220; $matches['from_status2'] = array(200,250,202,204,206,207,208,223);
						$matches['activity']="Груз ".$order['day_back']." дней на складе - возврат"; 
						$matches['alert_at']="NOW()"; 
					} else {
						$matches['status_step2'] = 204; $matches['from_status2'] = array(200,250,202,205,210,225);
						$matches['activity']="Груз прибыл на склад получателя"; 
					}	
				} else {
				preg_match($cargo_end_template,$np_reply,$matches);
				if (empty($matches)) { preg_match($cargo_end_template2,$np_reply,$matches); }
				if(!empty($matches)) { $matches['activity']="Груз поставлен на удаление"; $matches['msg']='Поставлен на утилизацию!'; $matches['status_step2'] = 241; $matches['from_status2'] = array(200,250,202,204,205,206,208,220,225,230,240);} else {
					preg_match($cargo_end_free_template,$np_reply,$matches);
					if(!empty($matches)) { $matches['activity']="Закончился бесплатный срок хранения груза"; $matches['msg']='Плата за хранение!'; $matches['status_step2'] = 220; $matches['from_status2'] = array(200,250,202,204,206,208,225); $matches['alert_at']="NOW()";} else {				
						preg_match($cargo_arrive_template,$np_reply,$matches);
						if(!empty($matches)) { $matches['activity']="Груз приехал на склад получателя (предоплата)"; $matches['msg']='Прибыл (предоплата)<br/>'.$matches['arrival_date']; $matches['status_step2'] = 205; $matches['status_step3'] = 311; $matches['from_status2'] = array(200,250,202,204); $matches['from_status3'] = array(301,302);} else {
							preg_match($cargo_received_backshipment_template,$np_reply,$matches);
							if(!empty($matches)) { $matches['activity']="Клиент отправил деньги"; $matches['msg']='Получен!<br/>'.$matches['received_date']; $matches['status_step2'] = 210; $matches['status_step3'] = 301; $matches['from_status2'] = array(200,250,202,204,206,207,208,209,220,221,222,223,224); $matches['from_status3'] = array();} else {
								preg_match($cargo_received_template,$np_reply,$matches);
								if(!empty($matches)) { $matches['activity']="Клиент получил груз (предоплата)"; $matches['msg']='Получен (предоплата)<br/>'.$matches['received_date']; $matches['status_step2'] = 209; $matches['status_step3'] = 311; $matches['from_status2'] = array(200,250,202,204,205,221,224); $matches['from_status3'] = array(301,302);} else {
									preg_match($cargo_reject_return_template,$np_reply,$matches);
									if(!empty($matches)) { $matches['activity']="Клиент отказался от груза - возврат"; $matches['msg']='Возврат!'; $matches['status_step2'] = 221; $matches['status_step3'] = 318; $matches['from_status2'] = array(200,250,202,204,206,208,210,222,220); $matches['from_status3'] = array(301, 302);} else {
										preg_match($cargo_reject_template,$np_reply,$matches);
										if(!empty($matches)) { $matches['activity']="Клиент отказался от груза - подать заявление"; $matches['msg']='Отказ!'; $matches['status_step2'] = 225; $matches['from_status2'] = array(200,250,202,204,206,208,220,210,222); $matches['status_step3'] = 0; $matches['from_status3'] = array(301,310,311,312); $matches['alert_at']="NOW()"; $matches['id_back'] = 'н/д';} else {
											preg_match($cargo_payed_transfer_template,$np_reply,$matches);
											if(!empty($matches)) { $matches['activity']="Клиент отправил деньги - перевод"; $matches['msg']='Получен!<br/><i>Денежный перевод</i>: '.$matches['transfer_date']; $matches['status_step2'] = 210; $matches['status_step3'] = 312; $matches['from_status2'] = array(200,250,202,204,206,208,220,221,222,224); $matches['from_status3'] = array(301,302);} else {
												preg_match($cargo_resend_template,$np_reply,$matches);
												if(!empty($matches)) { $matches['activity']="Изменен адрес получателя"; $matches['msg']='Изменение получателя: '.$matches['id_new']; $matches['status_step2'] = 230; $matches['from_status2'] = array(200,250,202,204,206,208,220,221,224); $matches['status_step3'] = 321; $matches['from_status3'] = array(318,320,321,310,311,312,301,302);} else {
													preg_match($cargo_removed_template,$np_reply,$matches);
													if(!empty($matches)) { $matches['activity']="Прекращено хранение груза!"; $matches['msg']='Выбросили!'; $matches['status_step2'] = 242; $matches['from_status2'] = array(200,250,202,204,205,206,208,220,221,224);} else {
														preg_match($cargo_ontheway_template,$np_reply,$matches);
														if(!empty($matches)) {
															$matches['activity']="Груз в пути!"; $matches['msg']='Прибудет: '.$matches['arrival_date']; 
															$at_adr_date = new DateTime($matches['arrival_date']);
															$now_date = new DateTime('now');
															$interval = $now_date->diff($at_adr_date);
															$interval = intval($interval->format('%r%d'));
															if ($interval <= -1) { $matches['msg'] = 'ОШИБКА: Дата прибытия прошла: '.$matches['arrival_date']; }
														} else {
															$matches['msg']='Ошибка!';
														}									
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		$match_result = array();		
		foreach($matches as $key=>$value) {
			if(intval($key) == 0 AND $key != '0') {
				$match_result[$key] = $value;
			}
		}		
				
		if ($order['status_step2'] == '0' or in_array($order['status_step2'], $matches['from_status2'] ? $matches['from_status2'] : array())) {
			$match_result['new_s2'] = $match_result['status_step2'];
		}
		else {
			$match_result['new_s2'] = $order['status_step2'];
		}
		if (($matches['status_step3'] or $matches['status_step3']=='0') and ($order['status_step3'] == '0' or in_array($order['status_step3'], $matches['from_status3'] ? $matches['from_status3'] : array()))) {
			$match_result['new_s3'] = $matches['status_step3'];
		} else {
			$match_result['new_s3'] = $order['status_step3'];
		}
				
		$update_query = "UPDATE orders SET ".($order['status_step2'] != $match_result['new_s2'] ? ('status_step2 = :new_s2, '.($match_result['alert_at'] ? "alert_at=".$match_result['alert_at'].", ":"")) : '').($order['status_step3'] != $match_result['new_s3'] ? 'status_step3 = :new_s3, ' : '')." newpost_answer=:answer, newpost_last_update=NOW() ".($match_result['id_back'] ? ", newpost_backorder=:id_back" : "")." WHERE id = :order_id"; 		
		if ($match_result['id_back']) {
			$update_query_params = array(':order_id' => $order['id'], ':answer' => json_encode($match_result), ':id_back' => $match_result['id_back'] == 'н/д' ? $order['newpost_backorder'] : $match_result['id_back']); 
		} else {
			$update_query_params = array(':order_id' => $order['id'], ':answer' => json_encode($match_result)); 
		}	
		
		if ($order['status_step2'] != $match_result['new_s2']) {
			$update_query_params = array_merge($update_query_params, array(':new_s2' => $match_result['new_s2']));
		}
		if ($order['status_step3'] != $match_result['new_s3']) {
			$update_query_params = array_merge($update_query_params, array(':new_s3' => $match_result['new_s3']));
		}	
					 
		try{	$update_stmt = $db->prepare($update_query); 
				$update_result = $update_stmt->execute($update_query_params); } 
		catch(PDOException $ex){ echo ("Невозможно выполнить запрос: " . $ex->getMessage()); }

		if ($order['status_step2'] != $match_result['new_s2'] || $order['status_step3'] != $match_result['new_s3']) {
			$aquery = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
						(	NOW(),
							:order_id,
							:user_id,
							:activity,
							:details )";
			
			$aquery_params = array( 
				':details' => $match_result['msg'] ? $match_result['msg'] : '',
				':activity' => $match_result['activity'] ? $match_result['activity'] : '',
				':user_id' => 1,
				':order_id' => $order['id']
			); 
			
			try{ 
				$astmt = $db->prepare($aquery); 
				$aresult = $astmt->execute($aquery_params); 
			} 
			catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }		
		}
		
		$orders_count += 1;
	}
	
	$query = "SELECT * FROM orders WHERE NOT newpost_backorder IS NULL AND newpost_backorder != '' AND newpost_backorder != '0' AND (:order_id IS NULL OR id = :order_id) AND (orders.status_step1 = 0 OR orders.status_step1 > 50) AND (orders.status_step2 = 0 OR orders.status_step2 > 50) AND (orders.status_step3 = 0 OR orders.status_step3 > 50)"; 
	$query_params = array(':order_id' => $_GET['id']); 
	 
	try{	$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); } 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	
	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_TIMEOUT, 20); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			
	while($order = $stmt->fetch()) {
		$np_reply = newpost_state($curl, trim($order['newpost_backorder']));
		$newpost_status = $np_reply;
		
		$matches = array();
		preg_match($cargo_arrive_template,$np_reply,$matches);
		if(!empty($matches)) { 
			$matches['activity']=($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='312') ? 'Деньги прибыли':'Возврат прибыл';
			$matches['msg']=(($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='312') ? 'Деньги прибыли':'Возврат прибыл').':<br/>'.$matches['arrival_date'];
			$matches['status_step3'] = (($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='312') ? 310:320); 
		} else {
			$matches = array();
			preg_match($cargo_received_template,$np_reply,$matches);
			if(!empty($matches)) { 
				$matches['status_step3'] = (($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='311' or $order['status_step3']=='312') ? 311:321);
				$matches['activity']=($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='311' or $order['status_step3']=='312') ? 'Деньги получены':'Возврат получен';
				$matches['msg']=(($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='311' or $order['status_step3']=='312') ? 'Деньги получены':'Возврат получен').':<br/>'.$matches['received_date'];
			} else {
				$matches = array();
				preg_match($cargo_ontheway_template,$np_reply,$matches);
				if(!empty($matches)) {
					$matches['activity']=($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='311' or $order['status_step3']=='312') ? 'Деньги в пути':'Возврат в пути';
					$matches['msg']=(($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='311' or $order['status_step3']=='312') ? 'Деньги прибудут':'Возврат прибудет').':<br/>'.$matches['arrival_date'];
				} else {
					$matches['msg']='Ошибка!';
				}
			}
		}					
			
		$match_result = array();		
		foreach($matches as $key=>$value) {
			if(intval($key) == 0 AND $key != '0') {
				$match_result[$key] = $value;
			}
		}		
				
		$update_query = "UPDATE orders SET ".(($match_result['status_step3'] and $match_result['status_step3'] != '0' and $order['status_step3'] != $match_result['status_step3']) ? 'status_step3 = :status_step3, ' : '')." newpost_backorder_answer=:answer, newpost_last_backorder_update=NOW() WHERE id = :order_id"; 
		$update_query_params = array(':order_id' => $order['id'], ':answer' => json_encode($match_result)); 
		
		if ($match_result['status_step3'] and $match_result['status_step3'] != '0' and $order['status_step3'] != $match_result['status_step3']) {
			$update_query_params = array_merge($update_query_params, array(':status_step3' => $match_result['status_step3']));
		}	
		
		try{	$update_stmt = $db->prepare($update_query); 
				$update_result = $update_stmt->execute($update_query_params); } 
		catch(PDOException $ex){ echo ("Невозможно выполнить запрос: " . $ex->getMessage()); }

		if ($match_result['status_step3'] and $match_result['status_step3'] != '0' and $order['status_step3'] != $match_result['status_step3']) {
			$aquery = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
						(	NOW(),
							:order_id,
							:user_id,
							:activity,
							:details		)";
			
			$aquery_params = array( 
				':details' => $match_result['msg'] ? $match_result['msg'] : '',
				':activity' => $match_result['activity'] ? $match_result['activity'] : '',
				':user_id' => 1,
				':order_id' => $order['id']
			); 
			
			try{ 
				$astmt = $db->prepare($aquery); 
				$aresult = $astmt->execute($aquery_params); 
			} 
			catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }		
		 
		}
		
		$orders_count += 1;
	}
			
	curl_close($curl);
	$runtime = microtime(true) - $runtime;
	echo "Обработаны статусы ".$orders_count." заказов Новой Почты за ".number_format($runtime, 1)." секунд";
?>