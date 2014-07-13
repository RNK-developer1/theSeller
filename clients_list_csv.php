<?php

    require("config.php");

    if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
        header("Location: index.php");
        die("Перенаправление: index.php");
    }

    $seller_id = (($_GET['seller_id'] or $_GET['seller_id'] == '0') ? $_GET['seller_id'] : $_SESSION['user']['id']);
    $seller_username = "none";

    $page_start = 0;

    $query = "
    SELECT SQL_CALC_FOUND_ROWS orders.fio, orders.phone, orders.email,
        IF (orders.referrer LIKE '%vk.com%', referrer, '') AS vk_url,
        users.username as seller_name,
        GROUP_CONCAT(orders.item SEPARATOR '; ') as good,
        GROUP_CONCAT(DATE(orders.created_at) SEPARATOR '; ') as date_create
    FROM orders
        LEFT JOIN users ON orders.owner_id = users.id
    WHERE fio IS NOT NULL AND fio <> '' ".
    " GROUP BY orders.phone
    ORDER BY orders.fio ASC";
//echo $query;
    $query_params =
        array(
        );

//var_dump($query_params);
    try {
        $stmt = $db->prepare($query);
        $result = $stmt->execute($query_params);
    } catch (PDOException $ex) {
        die("Невозможно выполнить запрос: " . $ex->getMessage());
    }

    $clients = $stmt->fetchAll();  //echo '<pre>'; var_dump($clients); echo '</pre>';

    // =============================================================================================
    // Определение общего количества уникальных клиентов

    $query = "SELECT FOUND_ROWS() as `cnt`";

    try{
        $stmt = $db->prepare($query);
        $result = $stmt->execute();
    }
    catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

    $clients_full_count = $stmt->fetch();

    $result = '';

    $head = '№;Предприниматель;ФИО;Телефон;Email;ID вконтакте;Товары'."\r\n";

    $client_number = $page_start + 1;
    foreach ($clients as $client) {

        $result .= $client_number++ . ";";
        if (!empty($client['seller_name']))
            $result .= trim($client['seller_name']) . ";";
        else
            $result .= ";";
        if (!empty($client['fio']))
            $result .= trim($client['fio']) . ";";
        else
            $result .= ";";
        if (!empty($client['phone']))
            $result .= trim($client['phone']) . ";";
        else
            $result .= ";";
        if (!empty($client['email']))
            $result .= trim($client['email']) . ";";
        else
            $result .= ";";

        $vk_error = false;
        $vk_id = $client['vk_url'];
        $pattern = '/id=([0-9]+)/';
        if (preg_match($pattern, $vk_id, $matches)) {
            $vk_id = 'id' . $matches[1];
        }
        $pattern = '/im\?.*sel=([a-zA-Z0-9_]+)/';
        if (preg_match($pattern, $vk_id, $matches)) {
            $vk_id = 'id' . $matches[1];
        }
        $pattern = '/album([0-9]+)/';
        if (preg_match($pattern, $vk_id, $matches)) {
            $vk_id = $matches[1];
        }
        $pattern = '/(id[0-9]+)/';
        if (preg_match($pattern, $vk_id, $matches)) {
            $vk_id = $matches[0];
        }
        $pattern_away = '/(vk.com\/away)/';
        $pattern_feed = '/(vk.com\/feed)/';
        $pattern_app = '/(vk.com\/app)/';
        $pattern_friends = '/(vk.com\/friends)/';
        $pattern_login = '/(vk.com\/login)/';
        $pattern_settings = '/(vk.com\/settings)/';
        $pattern_vk = '/(vk.com\/vk)/';
        $pattern_im = '/(vk.com\/im)/';
        if (
            (!preg_match($pattern_app, $vk_id, $matches)) and
            (!preg_match($pattern_friends, $vk_id, $matches)) and
            (!preg_match($pattern_settings, $vk_id, $matches)) and
            (!preg_match($pattern_login, $vk_id, $matches)) and
            (!preg_match($pattern_feed, $vk_id, $matches)) and
            //(!preg_match($pattern_im, $vk_id, $matches)) and
            (!preg_match($pattern_vk, $vk_id, $matches)) and
            (!preg_match($pattern_away, $vk_id, $matches))) {
            $pattern = '/vk.com\/([a-zA-Z0-9_]+)/';
            if (preg_match($pattern, $vk_id, $matches)) {
                $vk_id = $matches[1];
            }
        } else {
            $vk_id = 'Некорректные данные о профиле ВКонтакте ';//('. $vk_id .')';
            $vk_error = true;
        }

        if (!empty($client['$vk_id'])) {
            $result .= $vk_id . ";";
        }
        else
            $result .= ";";

        if (!empty($client['good']))
            $good = explode('; ', $client['good']);
        if (!empty($client['date_create']))
            $date_create = explode('; ', $client['date_create']);
        $result .= '"';
        for ($i=0;$i<sizeof($good);$i++) {
            // if ($i = 0 )
            //     $result .= $date_create[$i] . ', ' . $good[$i] . ";";
            // else
                $result .= $date_create[$i] . ', ' . $good[$i] . "; ";
        }
        $result .= '"' . "\r\n";
    }

    header('Content-Disposition: attachment; filename=clients_list.csv;charset=UTF-8');
    echo $head . $result;
