<?php

    $debugip = array(
        '46.119.193.127'
        );
    if (in_array($_SERVER['HTTP_X_REAL_IP'], $debugip)
        || in_array($_SERVER['REMOTE_ADDR'], $debugip)) {
        //ini_set("display_errors","1");
        //error_reporting(E_ALL);
    }

    include_once ('lib/simple_html_dom.php');
    $html_src = null;
    $status_array = array();
    $results = array();
    $strong_array = array(
        'direct' => 'Маршрут:',
        'transfer_date' => 'Дата прибытия:',
        'adress' => 'Адрес доставки:',
        'backshipment' => 'Обратная доставка:',
        'current_location' => 'Текущее местоположение:',
        'pay_info' => 'Информация про оплату:',
        'document_list' => 'Документы для получения отправления:',
        'weight' => 'Вес отправления:',
        'price' => 'Сумма к оплате:',
        'arrival' => 'Ориентировочная дата доставки:'
        );

    // Функция получения информации о состоянии груза
    // по номеру декларации (экспресс-накладной)
    // $dec_num - номер декларации
    // возвращает htm-код блока результатов поиска
    function newpost_state($dec_num)
    {
        global $results, $strong_array, $status_array;

        $status_array = array(
            // Cоздана электронная.*стадии обработки,
            'cargo_declarated' => array(
                'status_flag' => false,
                'status_name' =>
                    'Cоздана электронная.*стадии обработки',
                ),
            // Отправление прибыло, обратная доставка заказана
            'cargo_arrive_backshipment' => array(
                'status_flag' => false,
                'status_name' => 'Отправление прибыло',
                'backshipment' => true,
                ),
            // Отправление прибыло, обратная доставка не заказана
            'cargo_arrive' => array(
                'status_flag' => false,
                'status_name' => 'Отправление прибыло',
                'backshipment' => false,
                ),
            // Отправление получено, обратная доставка отправлена
            'cargo_received_backshipment' => array(
                'status_flag' => false,
                'status_name' => 'Отправление получено',
                'backshipment' => true,
                ),
            // Отправление получено, обратная доставка не заказана
            'cargo_received' => array(
                'status_flag' => false,
                'status_name' => 'Отправление получено',
                'backshipment' => false,
                ),
            // Получатель отказался от получения отправления,
            // движение груза
            'cargo_reject_return' => array(
                'status_flag' => false,
                'status_name' => 'Получатель отказался от получения отправления',
                'backshipment' => false,
                ),
            // Получатель отказался от получения отправления,
            'cargo_reject' => array(
                'status_flag' => false,
                'status_name' => 'Получатель отказался от получения отправления',
                'return' => true,
                ),
            // Денежный перевод отправлено | Денежный перевод выдан,
            'cargo_payed_transfer' => array(
                'status_flag' => false,
                'status_name' => 'Отправление прибыло',
                'return' => false,
                ),
            // Отправление переадресовано,
            'cargo_resend' => array(
                'status_flag' => false,
                'status_name' => 'Отправление переадресовано',
                ),
            // Прекращено хранение отправления,
            'cargo_removed' => array(
                'status_flag' => false,
                'status_name' => 'Прекращено хранение отправления',
                ),
            // Ориентировочная дата доставки,
            'cargo_ontheway' => array(
                'status_flag' => false,
                'status_name' => 'Ориентировочная дата доставки',
                ),
            // Закончился бесплатный срок хранения груза,
            'cargo_end_free' => array(
                'status_flag' => false,
                'status_name' =>
                    'Закончился бесплатный срок хранения груза',
                ),
            // Ваш груз поставлен на удаление,
            'cargo_end_1' => array(
                'status_flag' => false,
                'status_name' =>
                    'Ваш груз поставлен на удаление',
                ),
            // Истек срок хранения отправления,
            'cargo_end_2' => array(
                'status_flag' => false,
                'status_name' =>
                    'Истек срок хранения отправления',
                ),
            );

        $results = array();

        $url= 'http://novaposhta.ua/tracking/' .
            '?cargo_number=' . $dec_num . '&language=ru';

        $data = file_get_html($url,
            false, null, -1, -1, true, true, 'utf-8');
        if($data->innertext!=''
            and count($data->find('div.highlight'))){
            foreach($data->find('div.highlight') as $highlight){
                // Результат поиска
                $html_src = $highlight;

                // Поиск в абзацах
                $highlight_p = $highlight->find('p');
                foreach($highlight_p as $p) {
                    $p_text = $p->plaintext;

                    $strong = $p->find('strong');
                    //echo $strong[0]->innertext;
                    if (isset($strong[0])) {
                        $strong_block = $strong[0];
                        //echo trim($strong[0]->innertext) . '<br>';
                        foreach ($strong_array as $key=>$value) {
                            if (trim($strong[0]->innertext) == $value) {
                                $results[$key] = trim(substr(trim($p_text),
                                    strlen($strong[0]->innertext)));

                                if ($key == 'weight') {
                                    if (preg_match('/Сумма к оплате:\s(.*)/', trim($p_text), $cash_amount)) {
                                        $results['cash_amount'] = $cash_amount[1];
                                    }
                                }

                            }
                        }
                    }
                }


                // Поиск в ссылках
                $highlight_a = $highlight->find('a');
                foreach($highlight_a as $a) {
                    $href = $a->href;
                    if (preg_match('/\/office\/view\/id\//', $href)) {
                        $warehouse = substr($href,
                            strlen('/office/view/id/'));
                        $results['warehouse'] = $warehouse;
                        $results['address'] = $a->innertext;
                    }
                    if (preg_match('/\/tracking\/\?cargo_number=/', $href)) {
                        $id_back = substr($href,
                            strlen('/tracking/?cargo_number='));
                        $results['id_back'] = $id_back;
                    }
                }

                // Поиск в текстах
                $highlight_text = $highlight->find('text');
                foreach($highlight_text as $text) {
                    //echo $text . '<br>';

                    if (preg_match('/Отправление получено/', $text)) {
                        $results['status'] = 'Отправление получено';
                        if ($results['backshipment'] ==
                            'Обратная доставка отправлена') {
                            $results['status_back'] = 'Обратная доставка отправлена';
                            $status_array['cargo_received_backshipment']['status_flag'] = true;
                        } else {
                            $status_array['cargo_received']['status_flag'] = true;
                        }
                        if (preg_match('/\d\d\.\d\d\.\d\d\d\d/', $text, $received_date)) {
                            $results['received_date'] = $received_date[0];
                        }
                    }

                    if (preg_match('/Отправление прибыло/', $text)) {
                        $results['status'] = 'Отправление прибыло';
                        if ($results['backshipment'] ==
                            'Заказана услуга обратной доставки') {
                            $results['status_back'] = 'Заказана услуга обратной доставки';
                            $status_array['cargo_arrive_backshipment']['status_flag'] = true;
                        } else {
                            $status_array['cargo_arrive']['status_flag'] = true;
                        }
                    }

                    if (preg_match('/Получатель отказался от получения отправления/', $text)) {
                        $results['status'] = 'Получатель отказался от получения отправления';
                        if ($results['id_back']) {
                            $status_array['cargo_reject_return']['status_flag'] = true;
                        } else {
                            $status_array['cargo_reject']['status_flag'] = true;
                        }
                    }

                    if (preg_match('/Напоминаем, что через\s(\d+)\sрабочих дней от даты прибытия\s(\d\d\.\d\d\.\d\d\d\d)\sбудут начисляться дополнительные средства за хранение/', $text, $data)) {
                        $results['days_keep'] = $data[1];
                        $results['arrival_date'] = $data[2];
                    }
                }
            }
        }

        foreach ($results as $key=>$value) {
            if ($value == 'Денежный перевод отправлено') {
                $results['status_back'] = 'Денежный перевод отправлено';
                $status_array['cargo_payed_transfer']['status_flag'] = true;
            }
            if ($value == 'Денежный перевод выдан') {
                $results['status_back'] = 'Денежный перевод выдан';
                $status_array['cargo_payed_transfer']['status_flag'] = true;
            }
            if (!empty($results['arrival'])) {
                $results['status'] = 'Ориентировочная дата доставки';
                $results['arrival_date'] = $results['arrival'];
                $status_array['cargo_ontheway']['status_flag'] = true;
            }

        }
        return $html_src;
    }


    $cargo_arrive_backshipment_template =
        //'~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr>'.
        '~<div class="highlight"><p><strong>Маршрут:</strong>(?<direct>.*?)</p>'.
        //'<tr><td class="tracking">Текущее местоположение</td>'.
        '<strong>Текущее местоположение:</strong>'.
        //'<td>(?<status>Отправление прибыло). Приглашаем получить по адресу: <a href="/map/warehouse/id/(?<warehouse>\d*?)" target="__blank">(?<address>.*?)</a>.*?Напоминаем, что через (?<days_keep>\d+?) рабочих дней от даты прибытия \((?<arrival_date>\d\d\.\d\d\.\d\d\d\d)\) будут начисляться дополнительные средства за хранение.</td></tr>'.
        '(?<status>Отправление прибыло) в<a href="/office/view/id/(?<warehouse>\d.*)/"><span>(?<address>.*?)</span></a><p>Напоминаем, что через (?<days_keep>\d+?) рабочих дней от даты прибытия (?<arrival_date>\d\d\.\d\d\.\d\d\d\d) будут начисляться дополнительные средства за хранение.</p>'.
        //'<tr><td class="tracking">Обратная доставка</td><td>(?<status_back>Заказана услуга обратной доставки).</td></tr>'.
        '<p><strong>Обратная доставка:</strong>(?<status_back>Заказана услуга обратной доставки)</p>'.
        //'<tr><td class="tracking">Информация о оплате</td>.*?Сумма .*? - (?<cash_amount>.*?)</td>~';
        '<p><strong>.*ция про оплату:</strong>.*Сумма .* - (?<cash_amount>.*?)</p>~';

    $cargo_arrive_template =
        //'~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr>'.
        '~<div class="highlight"><p><strong>Маршрут:</strong>(?<direct>.*?)</p>'.
        //'<tr><td class="tracking">Текущее местоположение</td>'.
        '<strong>Текущее местоположение:</strong>'.
        //'<td>(?<status>Отправление прибыло). Приглашаем получить по адресу: <a href="/map/warehouse/id/(?<warehouse>\d*?)" target="__blank">(?<address>.*?)</a>.*?Напоминаем, что через (?<days_keep>\d+?) рабочих дней от даты прибытия \((?<arrival_date>\d\d\.\d\d\.\d\d\d\d)\) будут начисляться дополнительные средства за хранение.</td>~';
        '(?<status>Отправление прибыло) в<a href="/office/view/id/(?<warehouse>\d.*)/"><span>(?<address>.*?)</span></a><p>Напоминаем, что через (?<days_keep>\d+?) рабочих дней от даты прибытия (?<arrival_date>\d\d\.\d\d\.\d\d\d\d) будут начисляться дополнительные средства за хранение.</p>~';

    $cargo_received_backshipment_template =
        //'~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr>'.
        '~<div class="highlight"><p><strong>Маршрут:</strong>(?<direct>.*?)</p>'.
        //'<tr><td class="tracking">Текущее местоположение</td>'.
        '<strong>Текущее местоположение:</strong>'.
        //'<td>(?<status>Отправление получено) (?<received_date>\d\d\.\d\d\.\d\d\d\d)( по адресу: <a href="/map/warehouse/id/)?(?<warehouse>\d*?)?(" target="__blank">)?(?<address>.*?)?(</a></td>)?'.
        '(?<status>Отправление получено).*(?<received_date>\d\d\.\d\d\.\d\d\d\d).*(по адресу<a href="/office/view/id/)(?<warehouse>\d*).*(/"><span>)(?<address>.*)?(</span></a>)' .
        //'.*?<td>(?<status_back>Обратную доставку отправлено).*?<form.*?id="(?<id_back>.*?)"~';
        '<p><strong>Обратная доставка:</strong>(?<status_back>Обратная доставка отправлена)</p><a href="/tracking/\?cargo_number=(?<id_back>.*)">Просмотреть движение груза~';

    $cargo_received_template =
        //'~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr>'.
        '~<div class="highlight"><p><strong>Маршрут:</strong>(?<direct>.*?)</p>'.
        //'<tr><td class="tracking">Текущее местоположение</td>'.
        '<strong>Текущее местоположение:</strong>'.
        //'<td>(?<status>Отправление получено) (?<received_date>\d\d\.\d\d\.\d\d\d\d)( по адресу: <a href="/map/warehouse/id/)?(?<warehouse>\d*?)?(" target="__blank">)?(?<address>.*?)?(</a></td>)?'.
        '(?<status>Отправление получено).*(?<received_date>\d\d\.\d\d\.\d\d\d\d?).*(по адресу<a href="/office/view/id/)(?<warehouse>\d*).*([а-я]/"><span>)(?<address>.*)(</span></a><table border)~';

    $cargo_reject_return_template =
        //'~<td>(?<status>Получатель отказался от получения отправления)'.
        '~</strong>(?<status>Получатель отказался от получения отправления.  )'.
        //'.*<form.*?id="(?<id_back>.*?)">~';
        '(<a href="/tracking/\?cargo_number=)(?<id_back>.*?)">Просмотреть движение груза~';

    $cargo_reject_template =
        //'~<td>(?<status>Получатель отказался от получения отправления)~';
        '~</strong>(?<status>Получатель отказался от получения отправления).~';

    $cargo_payed_transfer_template =
        //'~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr>'.
        '~<div class="highlight"><p><strong>Маршрут:</strong>(?<direct>.*)</p>'.
        //'<tr><td class="tracking">Дата прибытия</td>'.
        '<p><strong>Дата прибытия:.*</strong>'.
        //'<td>(?<transfer_date>\d\d\.\d\d\.\d\d\d\d).*Информация о оплате</td>'.
        '.*(?<transfer_date>\d\d\.\d\d\.\d\d\d\d).*<p>.*<strong>Информация про оплату:</strong>' .
        //'<td>(?<status_back>Денежный перевод отправлено)</td>~';
        '.*(Денежный перевод выдан)~';

    $cargo_resend_template =
        //'~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr>'.
        '~<div class="highlight"><p><strong>Маршрут:</strong>(?<direct>.*?)</p>'.
        //'<tr><td class="tracking">Текущее местоположение</td>'.
        '<strong>Текущее местоположение:</strong>'.
        //'<td>(?<status>Отправление переадресовано)'.
        '.*(?<status>Отправление переадресовано)'.
        //'.*?<form.*?id="(?<id_new>.*?)">~';
        '.*<a href="/tracking/?cargo_number=(?<id_back>.*?)">Просмотреть движение груза~';

    $cargo_removed_template =
        '~(?<status>Прекращено хранение отправления)~';

    $cargo_ontheway_template =
        //'~<td class="tracking">Маршрут груза</td><td>(?<direct>.*?)</td></tr>'.
        '~<div class="highlight"><p><strong>Маршрут:</strong>(?<direct>.*?)</p>.*'.
        //'<tr><td class="tracking">(?<status>Дата прибытия)</td>'.
        //'<td>(?<arrival_date>\d\d.\d\d.\d\d\d\d?)</td>.*'.
        //'<td class="tracking">Адрес доставки</td>.*?(?<address>[Отделение|Заказано].*?)<~';
        '<p><strong>(?<status>Ориентировочная дата доставки):</strong> (?<arrival_date>\d\d.\d\d.\d\d\d\d?)</p>'.
        '<p><strong>Адрес доставки:</strong><a href="/office/view/id/?(?<warehouse>\d.*?)?(/"><span>)(?<address>[Отделение|Заказано].*?)</span></a></p>~';

    $cargo_end_free_template =
        '~Закончился бесплатный срок хранения груза~';

    $cargo_end_template =
        '~Ваш груз поставлен на удаление~';

    $cargo_end_template2 =
        '~Истек срок хранения отправления~';

    $cargo_declarated_template =
        '~Cоздана электронная.*стадии обработки~';


    //-------------------------------------------------------------------------------------------------------------------------------

    $runtime = microtime(true);
    require("config.php");

    $query = "
    SELECT orders.*, item.day_back as day_back
    FROM orders
        LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id
    WHERE
        NOT newpost_id IS NULL AND
        newpost_id != '' AND
        newpost_id != '0' AND
        (:order_id IS NULL OR id = :order_id) AND
        (orders.status_step1 = 0 OR orders.status_step1 > 50) AND
        (orders.status_step2 = 0 OR orders.status_step2 > 50) AND
        (orders.status_step3 = 0 OR orders.status_step3 > 50)";
    $query_params = array(':order_id' => $_GET['id']);

    try{    $stmt = $db->prepare($query);
            $result = $stmt->execute($query_params); }
    catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

    $orders_count = 0;
    while($order = $stmt->fetch()) { //echo $order['newpost_id'];

        $np_reply = newpost_state(trim($order['newpost_id']));
        $newpost_status = $np_reply;

        $matches = array();
        preg_match($cargo_declarated_template,$np_reply,$matches);
        if(!empty($matches)) {
            $matches['activity']="Оформлена декларация";
            $matches['msg']='Оформлена декларация';
            $matches['status_step2'] = 250;
            $matches['from_status2'] = array(200);
        } else {
            //preg_match($cargo_arrive_backshipment_template,$np_reply,$matches);
            if($status_array['cargo_arrive_backshipment']['status_flag']) {
                $matches['direct'] = $results['direct'];
                $matches['status'] = $results['status'];
                $matches['warehouse'] = $results['warehouse'];
                $matches['address'] = $results['address'];
                $matches['days_keep'] = $results['days_keep'];
                $matches['arrival_date'] = $results['arrival_date'];
                $matches['status_back'] = $results['status_back'];
                $matches['cash_amount'] = $results['cash_amount'];
                $status_array['cargo_arrive_backshipment']['status_flag'] = false;

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
                if (empty($matches)) {
                    preg_match($cargo_end_template2,$np_reply,$matches);
                }
                if(!empty($matches)) {
                    $matches['activity']="Груз поставлен на удаление";
                    $matches['msg']='Поставлен на утилизацию!';
                    $matches['status_step2'] = 241;
                    $matches['from_status2'] = array(200,250,202,204,205,206,208,220,225,230,240);
                } else {
                    preg_match($cargo_end_free_template,$np_reply,$matches);
                    if(!empty($matches)) {
                        $matches['activity']="Закончился бесплатный срок хранения груза";
                        $matches['msg']='Плата за хранение!';
                        $matches['status_step2'] = 220;
                        $matches['from_status2'] = array(200,250,202,204,206,208,225);
                        $matches['alert_at']="NOW()";
                    } else {
                        //preg_match($cargo_arrive_template,$np_reply,$matches);
                        if($status_array['cargo_arrive']['status_flag']) {

                            $matches['direct'] = $results['direct'];
                            $matches['status'] = $results['status'];
                            $matches['warehouse'] = $results['warehouse'];
                            $matches['address'] = $results['address'];
                            $matches['days_keep'] = $results['days_keep'];
                            $matches['arrival_date'] = $results['arrival_date'];
                            $status_array['cargo_arrive']['status_flag'] = false;

                            $matches['activity']="Груз приехал на склад получателя (предоплата)";
                            $matches['msg']='Прибыл (предоплата)<br/>'.$matches['arrival_date'];
                            $matches['status_step2'] = 205; $matches['status_step3'] = 311;
                            $matches['from_status2'] = array(200,250,202,204);
                            $matches['from_status3'] = array(301,302);
                        } else {
                            //preg_match($cargo_received_backshipment_template,$np_reply,$matches);
                            if($status_array['cargo_received_backshipment']['status_flag']) {

                                $matches['direct'] = $results['direct'];
                                $matches['received_date'] = $results['received_date'];
                                $matches['warehouse'] = $results['warehouse'];
                                $matches['address'] = $results['address'];
                                $matches['status_back'] = $results['status_back'];
                                $matches['id_back'] = $results['id_back'];
                                $status_array['cargo_received_backshipment']['status_flag'] = false;

                                $matches['activity']="Клиент отправил деньги";
                                $matches['msg']='Получен!<br/>'.$matches['received_date'];
                                $matches['status_step2'] = 210;
                                $matches['status_step3'] = 301;
                                $matches['from_status2'] = array(200,250,202,204,206,207,208,209,220,221,222,223,224);
                                $matches['from_status3'] = array();
                            } else {
                                //preg_match($cargo_received_template,$np_reply,$matches);
                                if ($status_array['cargo_received']['status_flag']) {

                                    $matches['direct'] = $results['direct'];
                                    $matches['received_date'] = $results['received_date'];
                                    $matches['warehouse'] = $results['warehouse'];
                                    $matches['address'] = $results['address'];
                                    $status_array['cargo_received']['status_flag'] = false;

                                    $matches['activity']="Клиент получил груз (предоплата)";
                                    $matches['msg']='Получен (предоплата)<br/>'.$matches['received_date'];
                                    $matches['status_step2'] = 209;
                                    $matches['status_step3'] = 311;
                                    $matches['from_status2'] = array(200,250,202,204,205,221,224);
                                    $matches['from_status3'] = array(301,302);
                                } else {
                                    //preg_match($cargo_reject_return_template,$np_reply,$matches);
                                    if($status_array['cargo_reject_return']['status_flag']) {

                                        $matches['status'] = $results['status'];
                                        $matches['id_back'] = $results['id_back'];

                                        $matches['activity']="Клиент отказался от груза - возврат";
                                        $matches['msg']='Возврат!';
                                        $matches['status_step2'] = 221;
                                        $matches['status_step3'] = 318;
                                        $matches['from_status2'] = array(200,250,202,204,206,208,210,222,220);
                                        $matches['from_status3'] = array(301, 302);
                                    } else {
                                        //preg_match($cargo_reject_template,$np_reply,$matches);
                                        if($status_array['cargo_reject']['status_flag']) {

                                            $matches['status'] = $results['status'];

                                            $matches['activity']="Клиент отказался от груза - подать заявление";
                                            $matches['msg']='Отказ!';
                                            $matches['status_step2'] = 225;
                                            $matches['from_status2'] = array(200,250,202,204,206,208,220,210,222);
                                            $matches['status_step3'] = 0;
                                            $matches['from_status3'] = array(301,310,311,312);
                                            $matches['alert_at']="NOW()";
                                            $matches['id_back'] = 'н/д';
                                        } else {
                                            //preg_match($cargo_payed_transfer_template,$np_reply,$matches);
                                            if($status_array['cargo_payed_transfer']['status_flag']) {
                                                $matches['direct'] = $results['direct'];
                                                $matches['transfer_date'] = $results['transfer_date'];
                                                $matches['status_back'] = $results['pay_info'];
                                                $status_array['cargo_payed_transfer']['status_flag'] = false;
                                                $matches['activity']="Клиент отправил деньги - перевод";
                                                $matches['msg']='Получен!<br/><i>Денежный перевод</i>: '.$matches['transfer_date'];

                                                $matches['status_step2'] = 210;
                                                $matches['status_step3'] = 312;
                                                $matches['from_status2'] = array(200,250,202,204,206,208,220,221,222,224);
                                                $matches['from_status3'] = array(301,302);
                                            } else {
                                                preg_match($cargo_resend_template,$np_reply,$matches);
                                                if(!empty($matches)) {
                                                    $matches['activity']="Изменен адрес получателя";
                                                    $matches['msg']='Изменение получателя: '.$matches['id_new'];
                                                    $matches['status_step2'] = 230;
                                                    $matches['from_status2'] = array(200,250,202,204,206,208,220,221,224);
                                                    $matches['status_step3'] = 321;
                                                    $matches['from_status3'] = array(318,320,321,310,311,312,301,302);
                                                } else {
                                                    preg_match($cargo_removed_template,$np_reply,$matches);
                                                    if(!empty($matches)) {
                                                        $matches['activity']="Прекращено хранение груза!";
                                                        $matches['msg']='Выбросили!';
                                                        $matches['status_step2'] = 242;
                                                        $matches['from_status2'] = array(200,250,202,204,205,206,208,220,221,224);
                                                    } else {
                                                        //preg_match($cargo_ontheway_template,$np_reply,$matches);
                                                        if($status_array['cargo_ontheway']['status_flag']) {

                                                            $matches['direct'] = $results['direct'];
                                                            $matches['status'] = $results['status'];
                                                            $matches['arrival_date'] = $results['arrival_date'];
                                                            $matches['warehouse'] = $results['warehouse'];
                                                            $matches['address'] = $results['address'];
                                                            $status_array['cargo_ontheway']['status_flag'] = false;

                                                            $matches['activity']="Груз в пути!";
                                                            $matches['msg']='Прибудет: '.$matches['arrival_date'];
                                                            $at_adr_date = new DateTime($matches['arrival_date']);
                                                            $now_date = new DateTime('now');
                                                            $interval = $now_date->diff($at_adr_date);
                                                            $interval = intval($interval->format('%r%d'));
                                                            if ($interval <= -1) {
                                                                $matches['msg'] = 'ОШИБКА: Дата прибытия прошла: '.$matches['arrival_date'];
                                                            }
                                                        } else {
                                                            $matches['msg']='Ошибка!';
                                                            echo 'Ошибка! newpost_id: ' . $order['newpost_id'] . ' order_id:' .  $order['id'] . '<br>'; //print_r($order);
                                                            echo $html_src;
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

        try{ $update_stmt = $db->prepare($update_query);
             $update_result = $update_stmt->execute($update_query_params); }
        catch(PDOException $ex){ echo ("Невозможно выполнить запрос: " . $ex->getMessage()); }

        if ($order['status_step2'] != $match_result['new_s2'] || $order['status_step3'] != $match_result['new_s3']) {
         $aquery = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
                     (   NOW(),
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
//echo $orders_count;
//print_r($matches);
        $orders_count += 1;
    }


    $query = "
    SELECT * FROM orders
    WHERE NOT newpost_backorder IS NULL AND
        newpost_backorder != '' AND
        newpost_backorder != '0' AND
        (:order_id IS NULL OR id = :order_id)
        AND (orders.status_step1 = 0 OR orders.status_step1 > 50) AND
        (orders.status_step2 = 0 OR orders.status_step2 > 50) AND
        (orders.status_step3 = 0 OR orders.status_step3 > 50)";
    $query_params = array(':order_id' => $_GET['id']);

    try{
        $stmt = $db->prepare($query);
        $result = $stmt->execute($query_params);
    }
    catch(PDOException $ex){
        die("Невозможно выполнить запрос: " . $ex->getMessage());
    }

    while($order = $stmt->fetch()) { //echo $order['newpost_backorder'];
        $np_reply = newpost_state(trim($order['newpost_backorder']));
        $newpost_status = $np_reply;

        $matches = array();
        //preg_match($cargo_arrive_template,$np_reply,$matches);
        if($status_array['cargo_arrive']['status_flag']) {

            $matches['direct'] = $results['direct'];
            $matches['status'] = $results['status'];
            $matches['warehouse'] = $results['warehouse'];
            $matches['address'] = $results['address'];
            $matches['days_keep'] = $results['days_keep'];
            $matches['arrival_date'] = $results['arrival_date'];
            $status_array['cargo_arrive']['status_flag'] = false;

            $matches['activity']=($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='312') ? 'Деньги прибыли':'Возврат прибыл';
            $matches['msg']=(($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='312') ? 'Деньги прибыли':'Возврат прибыл').':<br/>'.$matches['arrival_date'];
            $matches['status_step3'] = (($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='312') ? 310:320);
        } else {
            $matches = array();
            //preg_match($cargo_received_template,$np_reply,$matches);
            if ($status_array['cargo_received']['status_flag']) {

                $matches['direct'] = $results['direct'];
                $matches['received_date'] = $results['received_date'];
                $matches['warehouse'] = $results['warehouse'];
                $matches['address'] = $results['address'];
                $status_array['cargo_received']['status_flag'] = false;

                $matches['status_step3'] = (($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='311' or $order['status_step3']=='312') ? 311:321);
                $matches['activity']=($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='311' or $order['status_step3']=='312') ? 'Деньги получены':'Возврат получен';
                $matches['msg']=(($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='311' or $order['status_step3']=='312') ? 'Деньги получены':'Возврат получен').':<br/>'.$matches['received_date'];
            } else {
                $matches = array();
                //preg_match($cargo_ontheway_template,$np_reply,$matches);
                if($status_array['cargo_ontheway']['status_flag']) {

                    $matches['direct'] = $results['direct'];
                    $matches['status'] = $results['status'];
                    $matches['arrival_date'] = $results['arrival_date'];
                    $matches['warehouse'] = $results['warehouse'];
                    $matches['address'] = $results['address'];
                    $status_array['cargo_ontheway']['status_flag'] = false;

                    $matches['activity']=($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='311' or $order['status_step3']=='312') ? 'Деньги в пути':'Возврат в пути';
                    $matches['msg']=(($order['status_step3']=='301' or $order['status_step3']=='310' or $order['status_step3']=='311' or $order['status_step3']=='312') ? 'Деньги прибудут':'Возврат прибудет').':<br/>'.$matches['arrival_date'];
                } else {
                    preg_match($cargo_end_template,$np_reply,$matches);
                    if (!empty($matches)) {
                        echo $matches['msg']='Ваш груз поставлен на удаление';
                    } else {
                        preg_match($cargo_end_template2,$np_reply,$matches);
                        if (!empty($matches)) {
                            echo $matches['msg']='Истек срок хранения отправления';
                        } else {
                        echo $matches['msg']='Ошибка!';
                        }
                    }
                    echo ' newpost_backorder: ' . $order['newpost_backorder'] . ' order_id:' .  $order['id'] . '<br>'; //print_r($order);
                    echo $html_src;
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

     try{    $update_stmt = $db->prepare($update_query);
             $update_result = $update_stmt->execute($update_query_params); }
     catch(PDOException $ex){ echo ("Невозможно выполнить запрос: " . $ex->getMessage()); }

     if ($match_result['status_step3'] and $match_result['status_step3'] != '0' and $order['status_step3'] != $match_result['status_step3']) {
         $aquery = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
                     (   NOW(),
                         :order_id,
                         :user_id,
                         :activity,
                         :details        )";

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
    //echo $orders_count;
//print_r($matches);
        $orders_count += 1;
    }

    $runtime = microtime(true) - $runtime;
    echo "Обработаны статусы ".$orders_count." заказов Новой Почты за ".number_format($runtime, 1)." секунд";
?>