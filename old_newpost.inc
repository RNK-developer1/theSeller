if (false) {
				$short_newpost_status = '';
				$short_newpost_status_back = '';
				$money_sent_id = '';
				$rec_adr = '';
				if ($ord['newpost_id'] != '' AND $ord['newpost_id'] != '0')
				{ 
					$matches = array();
					$newpost_full = newpost_state(trim($ord['newpost_id'])); 
					$rexp_item_at_address = '/Груз прибыл.*(\d\d\.\d\d\.\d\d\d\d)/';
					$rexp_item_received_date = '/Груз получен: (\d\d\.\d\d\.\d\d\d\d)/';
					$rexp_item_money_sent = '/<input type=\"hidden\" name=\"en\" value=(\d*)/';
					$rexp_item_on_the_way = '/Дата прибытия.*(\d\d\.\d\d\.\d\d\d\d)/';
					$rexp_rec_adr = '/<a.*Отделение.*:(.*)<\/a>/';
					 preg_match($rexp_item_at_address,$newpost_full,$matches); 
					$item_at_address = $matches[1];
					 preg_match($rexp_item_received_date,$newpost_full,$matches); 
					$item_received = $matches[1];
					 preg_match($rexp_item_money_sent,$newpost_full,$matches); 
					$money_sent_id = $matches[1];
					 preg_match($rexp_item_on_the_way,$newpost_full,$matches); 
					$item_on_the_way = $matches[1];
					 preg_match($rexp_rec_adr,$newpost_full,$matches); 
					$rec_adr = trim($matches[1]);
					
					if ($item_on_the_way != '') {
						$short_newpost_status = 'Прибудет '.$item_on_the_way;
					}
					if ($item_received != '' AND $money_sent_id != '')  {
						$short_newpost_status = 'Получен!<br/>'.$item_received;						
						
						if ($ord['status_step2'] != '210') {
							$query = " 
								UPDATE orders SET status_step2 = 210, status_step3 = 301 WHERE id = :id";		
							$query_params = array( 
								':id' => $ord['id']
							); 
							
							try{ 
								$stmt = $db->prepare($query); 
								$result = $stmt->execute($query_params); 
							} 
							catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
							
							$ord['status_step2'] = 210;
							
							$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
										(	NOW(),
											:order_id,
											:user_id,
											:activity,
											:details		)";
							
							$query_params = array( 
								':details' => print_r($ord,true),
								':activity' => 'Клиент отправил деньги',
								':user_id' => 1,
								':order_id' => $ord['id']
							); 
							
							try{ 
								$stmt = $db->prepare($query); 
								$result = $stmt->execute($query_params); 
							} 
							catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
						}
					}
					if ($item_received != '' AND $money_sent_id == '')  {
						$short_newpost_status = 'Возврат!<br/>'.$item_received;						
						
						$query = " 
							UPDATE orders SET status_step3 = 302 WHERE id = :id";		
						$query_params = array( 
							':id' => $ord['id']
						); 
						
						try{ 
							$stmt = $db->prepare($query); 
							$result = $stmt->execute($query_params); 
						} 
						catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
						
						$ord['status_step3'] = 302;
						
						$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
									(	NOW(),
										:order_id,
										:user_id,
										:activity,
										:details		)";
						
						$query_params = array( 
							':details' => print_r($ord,true),
							':activity' => 'Возврат в пути',
							':user_id' => 1,
							':order_id' => $ord['id']
						); 
						
						try{ 
							$stmt = $db->prepare($query); 
							$result = $stmt->execute($query_params); 
						} 
						catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
					}
					if ($item_at_address != '') {
						$at_adr_date = new DateTime($item_at_address);
						$now_date = new DateTime('now');
						$interval = $now_date->diff($at_adr_date);
						$interval = intval($interval->format('%R%d'));
						
						$short_newpost_status = '<b>Прибыл!</b><br/>'.$item_at_address.'<br/>Прошло дней: '.(-$interval);
						if ($ord['status_step2'] < '204') {
							$query = " 
								UPDATE orders SET status_step2 = 204 WHERE id = :id";		
							$query_params = array( 
								':id' => $ord['id']
							); 
							
							try{ 
								$stmt = $db->prepare($query); 
								$result = $stmt->execute($query_params); 
							} 
							catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
							
							$ord['status_step2'] = 204;
							
							$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
										(	NOW(),
											:order_id,
											:user_id,
											:activity,
											:details		)";
							
							$query_params = array( 
								':details' => print_r($ord,true),
								':activity' => 'Груз прибыл на склад получателя',
								':user_id' => 1,
								':order_id' => $ord['id']
							); 
							
							try{ 
								$stmt = $db->prepare($query); 
								$result = $stmt->execute($query_params); 
							} 
							catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
						}
												
						if ($interval == -2 AND $ord['status_step2'] == '204') {
							$query = " 
								UPDATE orders SET status_step2 = 206, alert_at = NOW() WHERE id = :id";		
							$query_params = array( 
								':id' => $ord['id']
							); 
							
							try{ 
								$stmt = $db->prepare($query); 
								$result = $stmt->execute($query_params); 
							} 
							catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
							
							$ord['status_step2'] = 206;
							
							$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
										(	NOW(),
											:order_id,
											:user_id,
											:activity,
											:details		)";
							
							$query_params = array( 
								':details' => print_r($ord,true),
								':activity' => 'Груз уже второй день на складе получателя',
								':user_id' => 1,
								':order_id' => $ord['id']
							); 
							
							try{ 
								$stmt = $db->prepare($query); 
								$result = $stmt->execute($query_params); 
							} 
							catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
						}
						if ($interval <= -5 AND ($ord['status_step2'] == '206' OR $ord['status_step2'] == '208' OR $ord['status_step2'] == '204')) {
							$query = " 
								UPDATE orders SET status_step2 = 220, alert_at = NOW() WHERE id = :id";		
							$query_params = array( 
								':id' => $ord['id']
							); 
							
							try{ 
								$stmt = $db->prepare($query); 
								$result = $stmt->execute($query_params); 
							} 
							catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
							
							$ord['status_step2'] = 220;
							
							$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
										(	NOW(),
											:order_id,
											:user_id,
											:activity,
											:details		)";
							
							$query_params = array( 
								':details' => print_r($ord,true),
								':activity' => 'Груз 5 дней на складе - возврат',
								':user_id' => 1,
								':order_id' => $ord['id']
							); 
							
							try{ 
								$stmt = $db->prepare($query); 
								$result = $stmt->execute($query_params); 
							} 
							catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
						}
					}
					
					if ($money_sent_id != '') {
						$newpost_full = newpost_state(trim($money_sent_id)); 
						preg_match($rexp_item_received_date,$newpost_full,$matches); 
						$item_received = $matches[1];
						preg_match($rexp_item_on_the_way,$newpost_full,$matches); 
						$item_on_the_way = $matches[1];
						preg_match($rexp_item_at_address,$newpost_full,$matches); 
						$item_at_address = $matches[1];						
						
						if ($item_on_the_way != '') {
							$short_newpost_status_back = 'Прибудут '.$item_on_the_way;
						}
						if ($item_received != '') {
							$short_newpost_status_back = 'Получены!<br/>'.$item_received;
							
							if ($ord['status_step3'] == '301' OR $ord['status_step3'] == '310') {
								$query = " 
									UPDATE orders SET status_step3 = 311 WHERE id = :id";		
								$query_params = array( 
									':id' => $ord['id']
								); 
								
								try{ 
									$stmt = $db->prepare($query); 
									$result = $stmt->execute($query_params); 
								} 
								catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
								
								$ord['status_step3'] = 311;
								
								$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
											(	NOW(),
												:order_id,
												:user_id,
												:activity,
												:details		)";
								
								$query_params = array( 
									':details' => print_r($ord,true),
									':activity' => 'Деньги получены',
									':user_id' => 1,
									':order_id' => $ord['id']
								); 
								
								try{ 
									$stmt = $db->prepare($query); 
									$result = $stmt->execute($query_params); 
								} 
								catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
							}													
						}
						if ($item_at_address != '') {
							$short_newpost_status_back = '<b>Прибыли!</b><br/>'.$item_at_address;
							
							if ($ord['status_step3'] == '301') {
								$query = " 
									UPDATE orders SET status_step3 = 310 WHERE id = :id";		
								$query_params = array( 
									':id' => $ord['id']
								); 
								
								try{ 
									$stmt = $db->prepare($query); 
									$result = $stmt->execute($query_params); 
								} 
								catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
								
								$ord['status_step3'] = 310;
								
								$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
											(	NOW(),
												:order_id,
												:user_id,
												:activity,
												:details		)";
								
								$query_params = array( 
									':details' => print_r($ord,true),
									':activity' => 'Деньги прибыли',
									':user_id' => 1,
									':order_id' => $ord['id']
								); 
								
								try{ 
									$stmt = $db->prepare($query); 
									$result = $stmt->execute($query_params); 
								} 
								catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
							}													
						}
					}					
					else if ($ord['newpost_backorder'] AND $ord['newpost_backorder'] != '') {
						$money_sent_id = $ord['newpost_backorder'];
						$newpost_full = newpost_state($money_sent_id); 
						preg_match($rexp_item_received_date,$newpost_full,$matches); 
						$item_received = $matches[1];
						preg_match($rexp_item_on_the_way,$newpost_full,$matches); 
						$item_on_the_way = $matches[1];
						preg_match($rexp_item_at_address,$newpost_full,$matches); 
						$item_at_address = $matches[1];						
						
						if ($item_on_the_way != '') {
							$short_newpost_status_back = 'Прибудет '.$item_on_the_way;
						}
						if ($item_received != '') {
							$short_newpost_status_back = 'Получен!<br/>'.$item_received;
																				
							if ($ord['status_step3'] == '302' OR $ord['status_step3'] == '320') {
								$query = " 
									UPDATE orders SET status_step3 = 321 WHERE id = :id";		
								$query_params = array( 
									':id' => $ord['id']
								); 
								
								try{ 
									$stmt = $db->prepare($query); 
									$result = $stmt->execute($query_params); 
								} 
								catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
								
								$ord['status_step3'] = 321;
								
								$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
											(	NOW(),
												:order_id,
												:user_id,
												:activity,
												:details		)";
								
								$query_params = array( 
									':details' => print_r($ord,true),
									':activity' => 'Возврат получен',
									':user_id' => 1,
									':order_id' => $ord['id']
								); 
								
								try{ 
									$stmt = $db->prepare($query); 
									$result = $stmt->execute($query_params); 
								} 
								catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
							}
						}
						if ($item_at_address != '') {							
							if ($ord['status_step3'] == '302') {
								$query = " 
									UPDATE orders SET status_step3 = 320 WHERE id = :id";		
								$query_params = array( 
									':id' => $ord['id']
								); 
								
								try{ 
									$stmt = $db->prepare($query); 
									$result = $stmt->execute($query_params); 
								} 
								catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
								
								$ord['status_step3'] = 320;
								
								$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
											(	NOW(),
												:order_id,
												:user_id,
												:activity,
												:details		)";
								
								$query_params = array( 
									':details' => print_r($ord,true),
									':activity' => 'Возврат прибыл',
									':user_id' => 1,
									':order_id' => $ord['id']
								); 
								
								try{ 
									$stmt = $db->prepare($query); 
									$result = $stmt->execute($query_params); 
								} 
								catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
							}
						}
					}					
				}
			}