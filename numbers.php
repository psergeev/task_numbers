<?php
    $GLOBALS['config'] = array(
        'default_backend' => 'file', // backend to store data can be 'redis' (need redis and pecl-redis) or 'file'
        'redis_host' => '127.0.0.1', // host for redis backend
        'redis_port' => 6379, // port for redis backend
        'redis_counter_key' => 'my_counter_key', // key in redis to store data
        'file_data_file' => 'data.txt' // file with name 'data.txt' and permissions 666 !!! must exists for 'file' backend !!!
    );

    $mode = $_GET['mode'];

    // list of available modes and functions for each
    $backend = $GLOBALS['config']['default_backend'];
    $list_of_modes = array('zero' => $backend . '_zero', 'inc' => $backend . '_inc', 'dec' => $backend . '_dec');

    // stop script for unknown mode
    if (!in_array($mode, array_keys($list_of_modes)))
    {
        print ('unknown mode');
        exit();
    }

    $b = call_user_func($GLOBALS['config']['default_backend'] . '_init'); // init backend
    print $list_of_modes[$mode]($b); // process data on backend

    function drop_error()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        exit();
    }

    // ---------------------------------- //
    // Set of functions for redis backend //
    // ---------------------------------- //

    function redis_init()
    {
        $redis = new Redis();
        if (!$redis->pconnect($GLOBALS['config']['redis_host'], $GLOBALS['config']['redis_port'])) drop_error();

        return $redis;
    }

    function redis_zero($redis)
    {
        if (!$redis->set($GLOBALS['config']['redis_counter_key'], 0)) drop_error();

        print 0;
    }

    function redis_inc($redis)
    {
        print $redis->incr($GLOBALS['config']['redis_counter_key']);
    }

    function redis_dec($redis)
    {
        print $redis->decrBy($GLOBALS['config']['redis_counter_key'], 10);
    }


    // --------------------------------- //
    // Set of functions for file backend //
    // --------------------------------- //

    function file_init()
    {
        $fh = fopen($GLOBALS['config']['file_data_file'], 'r+');

        if (!$fh) drop_error();

        if (flock($fh, LOCK_EX)) // flock will automatically wait until file will be locked
            return $fh;
        else
            drop_error();
    }

    function file_zero($fh)
    {
        write_to_file($fh, 0);

        print 0;
    }

    function file_inc($fh)
    {
        $data = read_from_file($fh);

        write_to_file($fh, ++$data);

        print $data;
    }

    function file_dec($fh)
    {
        $data = read_from_file($fh);
        $data -= 10;
        write_to_file($fh, $data);

        print $data;
    }

    function read_from_file($fh)
    {
        $data = fread($fh, 21);

        if ($data === false) drop_error();

        if (!$data) $data = 0;

        return intval($data);
    }

    function write_to_file($fh, $data)
    {
        rewind($fh);
        ftruncate($fh, 0);

        if (!fwrite($fh, $data)) drop_error();

        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
    }



