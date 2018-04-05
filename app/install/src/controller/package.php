<?php //-->
/**
 * This file is part of a Custom Project.
 * (c) 2016-2018 Acme Products Inc.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

/**
 * Render Package Search
 * 
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/package/search', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data
    if (!$request->hasStage('filter', 'active')) {
        $request->setStage('filter', 'active', 1);
    }

    // get the active filter
    $active = (bool) $request->getStage('filter', 'active');

    // get the package config
    $config = $this->package('global')->config('packages');

    // set package list action
    $request->setStage(0, 'list');
    // we just only need the data
    $request->setStage('data', true);

    // get the list of packages
    $this->trigger('package', $request, $response);
    
    // get the packages
    $packages = $response->getResults();

    // get the results
    $results = [];

    // if we have pacakges
    if (!empty($packages)) {
        // for each packages
        foreach ($packages as $key => $package) {
            // package not set to config?
            if (!isset($config[$key])) {
                // set it as inactive package
                $config[$key] = [ 'active' => false ];
            }

            // filter matched?
            if ($config[$key]['active'] !== $active) {
                unset($packages[$key]);
                continue;
            }

            // active flag
            $packages[$key]['active'] = $config[$key]['active'];

            // unset version
            if (isset($packages[$key]['version'])) {
                unset($packages[$key]['version']);
            }

            // get the version from config
            if (isset($config[$key]['version'])) {
                $packages[$key]['version'] = $config[$key]['version'];
            }

            // if package type is root
            if ($package['type'] == 'module') {
                // set package key
                $packages[$key]['key'] = str_replace('/', ':', substr($key, 1));
                // set package log key
                $packages[$key]['log_key'] = str_replace('/', '.', substr($key, 1));

                // set package path
                $packages[$key]['path'] = $this->package('global')->path('root') . '%s';
                $packages[$key]['path'] = sprintf($packages[$key]['path'], $key);

                // normalize type
                $packages[$key]['type'] = 'root';
            }

            // if package type is vendor
            if ($package['type'] == 'vendor') {
                // set package key
                $packages[$key]['key'] = str_replace('/', ':', $key);
                // set package log key
                $packages[$key]['log_key'] = str_replace('/', '.', $key);

                // set package path
                $packages[$key]['path'] = $this->package('global')->path('root') . '/vendor/%s';
                $packages[$key]['path'] = sprintf($packages[$key]['path'], $key);

                // get the composer path
                $composer = $packages[$key]['path'] . '/composer.json';

                // load the composer file
                $composer = json_decode(
                    file_get_contents(sprintf($composer, $key)),
                    true
                );
                
                // remove version
                if (isset($composer['version'])) {
                    unset($composer['version']);
                }

                // remove type
                if (isset($composer['type'])) {
                    unset($composer['type']);
                }

                // merge composer data
                $packages[$key] = array_merge($packages[$key], $composer);
            }

            $bootstrap = null;

            // load the bootstrap file
            try {
                $bootstrap = $this->package($key);
            } catch(\Exception $e) {
                $bootstrap = $this->register($key)->package($key);
            }

            // clone the bootstrap
            $reflection = new ReflectionClass($bootstrap);
            // get the methods
            $methods = $reflection->getProperty('methods');
            // make it accessible
            $methods->setAccessible(true);
            // get the methods
            $methods = $methods->getValue($bootstrap);

            // installable?
            if (isset($methods['install'])) {
                // set install flag
                $packages[$key]['installable'] = true;
            }

            // get install log
            $log = $this->package('global')->path('config') . '/packages/%s.install.php';
            $log = sprintf($log, $packages[$key]['log_key']);

            // if log file exists
            if (file_exists($log)) {
                // load the log file
                $packages[$key]['install_log'] = include($log);

                // pending status?
                if (strpos($packages[$key]['install_log']['status'], 'pending') !== false) {
                    $packages[$key]['install_pending'] = true;
                }
            }
        }
    }

    // $this->inspect($packages);

    // merge data
    $data = array_merge([ 'rows' => $packages, 'total' => count($packages) ], $request->getStage());

    //----------------------------//
    // 2. Render Template
    $class = 'page-install-package-search page-install';
    $data['title'] = $this->package('global')->translate('Packages');
    $body = $this->package('/app/install')->template('package', $data, [
        'package/_log-modal'
    ]);

    //set content
    $response
        ->setPage('title', $data['title'])
        ->setPage('class', $class)
        ->setContent($body);

    $this->trigger('admin-render-page', $request, $response);
});

/**
 * Render Packagists Search
 * 
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/package/packagist/search', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data
    
    // get registered package
    $registered = $this->package('global')->config('packages');

    // package results
    $results = [];
    // package total
    $total = 0;

    // if we have query
    if ($request->hasStage('q')) {
        // set action
        $request->setStage(0, 'search');
        // set query
        $request->setStage(1, $request->getStage('q'));

        // disable cli
        $request->setStage('data', true);

        // trigger package event
        $this->trigger('package', $request, $response);

        // get results
        $results = $response->get('json', 'results');
        // get total
        $total = $response->get('json', 'total');

        // if we have results
        if (is_array($results)) {
            // on each packages
            foreach($results as $key => $package) {
                // get package name
                $name = $package['name'];

                // format the unique key
                $results[$key]['key'] = str_replace('/', ':', $name);

                // inactive package?
                if (isset($registered[$name]['active'])
                && $registered[$name]['active'] == true) {
                    $results[$key]['active'] = true;
                }

                // installed?
                if (isset($registered[$name])) {
                    $results[$key]['installed'] = true;
                }

                // set install log key
                $results[$key]['log_key'] = str_replace('/', '.', $name);

                // recent log?
                $log = $this->package('global')->path('config') . '/packages/%s.install.php';
                $log = sprintf($log, $results[$key]['log_key']);

                // log file exists?
                if (file_exists($log)) {
                    $results[$key]['install_log'] = str_replace(
                        $this->package('global')->path('config') . '/packages/',
                        '',
                        $log
                    );

                    // load up log file
                    $log = include($log);

                    // status is set?
                    if (isset($log['status'])) {
                        // pending action?
                        if (strpos($log['status'], 'pending') > 0) {
                            // set pending flag
                            $results[$key]['install_pending'] = 1;
                        }

                        // get the latest status
                        $results[$key]['last_status'] = $log['status'];
                    }
                }
            }
        }
    }

    // set data
    $data = [ 'rows' => $results, 'total' => $total ];

    // stage data
    $stage = [];

    if ($request->getStage()) {
        $stage = $request->getStage();
    }

    // merge stage
    $data = array_merge($data, $stage);

    //----------------------------//
    // 2. Render Template
    $class = 'page-install-package-packagist-search page-install';
    $data['title'] = $this->package('global')->translate('Packagist Search');
    $body = $this->package('/app/install')->template('packagist', $data, [
        'package/_log-modal'
    ]);

    //set content
    $response
        ->setPage('title', $data['title'])
        ->setPage('class', $class)
        ->setContent($body);

    $this->trigger('admin-render-page', $request, $response);
});

/**
 * Process Package Install
 * 
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/package/install/:name', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data

    if (!$request->getStage('type')) {
        $this->package('global')->flash('Invalid Request', 'error');
        return $this->package('global')->redirect('/admin/package/search');
    }

    // get and format name
    $name = $request->getStage('name');

    // if it's a root package
    if ($request->getStage('type') === 'root') {
        $name = '/' . str_replace(':', '/', $name);
    } else {
        $name = str_replace(':', '/', $name);
    }
    
    // get the root path
    $root = $this->package('global')->path('root');

    // NOTE: PHP Shell exec is a little bit tricky
    // for us to be able to execute a long running script
    // while redirecting all the output to a file, we need
    // to wrap the command via a shell script. Tricky...
    $exec = __DIR__ . '/../bin/package.sh %s install %s >> package-install.log &';
    $exec = sprintf($exec, $root, $name);

    // execute the command
    exec($exec);

    $this->package('global')->flash('Package is being installed. Please wait for the process to finish.', 'success');

    // redirect back to packagist search
    if ($request->getStage('type') == 'vendor') {
        return $this->package('global')->redirect('/admin/package/packagist/search?q=');
    }

    return $this->package('global')->redirect('/admin/package/search');
});

/**
 * Process Package Update
 * 
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/package/update/:name', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data

    if (!$request->getStage('type')) {
        $this->package('global')->flash('Invalid Request', 'error');
        return $this->package('global')->redirect('/admin/package/search');
    }

    // get and format name
    $name = $request->getStage('name');

    // if it's a root package
    if ($request->getStage('type') === 'root') {
        $name = '/' . str_replace(':', '/', $name);
    } else {
        $name = str_replace(':', '/', $name);
    }
    
    // get the root path
    $root = $this->package('global')->path('root');

    // NOTE: PHP Shell exec is a little bit tricky
    // for us to be able to execute a long running script
    // while redirecting all the output to a file, we need
    // to wrap the command via a shell script. Tricky...
    $exec = __DIR__ . '/../bin/package.sh %s update %s >> package-install.log &';
    $exec = sprintf($exec, $root, $name);

    // execute the command
    exec($exec);

    $this->package('global')->flash('Package is being updated. Please wait for the process to finish.', 'success');

    // redirect back to packagist search
    if ($request->getStage('type') == 'vendor') {
        return $this->package('global')->redirect('/admin/package/packagist/search?q=');
    }

    return $this->package('global')->redirect('/admin/package/search');
});


/**
 * Process Package Remove
 * 
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/package/remove/:name', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data

    if (!$request->getStage('type')) {
        $this->package('global')->flash('Invalid Request', 'error');
        return $this->package('global')->redirect('/admin/package/search');
    }

    // get and format name
    $name = $request->getStage('name');

    // if it's a root package
    if ($request->getStage('type') === 'root') {
        $name = '/' . str_replace(':', '/', $name);
    } else {
        $name = str_replace(':', '/', $name);
    }
    
    // get the root path
    $root = $this->package('global')->path('root');

    // NOTE: PHP Shell exec is a little bit tricky
    // for us to be able to execute a long running script
    // while redirecting all the output to a file, we need
    // to wrap the command via a shell script. Tricky...
    $exec = __DIR__ . '/../bin/package.sh %s remove %s >> package-install.log &';
    $exec = sprintf($exec, $root, $name);

    // execute the command
    exec($exec);

    $this->package('global')->flash('Package is being removed. Please wait for the process to finish.', 'success');

    // redirect back to packagist search
    if ($request->hasStage('redirect')) {
        return $this->package('global')->redirect($request->getStage('redirect'));
    }

    return $this->package('global')->redirect('/admin/package/search');
});

/**
 * Process Package Enable
 * 
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/package/enable/:name', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data
    if (!$request->getStage('type')) {
        $this->package('global')->flash('Invalid Request', 'error');
        return $this->package('global')->redirect('/admin/package/search');
    }
    
    // get package name
    $name = $request->getStage('name');
    // get package path
    $path = null;

    // if it's a root package
    if ($request->getStage('type') === 'root') {
        $name = '/' . str_replace(':', '/', $name);
        $path = $this->package('global')->path('root') . $name;
    } else {
        $name = str_replace(':', '/', $name);
        $path = $this->package('global')->path('root') . '/vendor/' . $name;
    }

    // load package config
    $config = $this->package('global')->config('packages');

    // if package is not registered
    if (!is_dir($path)) {
        $this->package('global')->flash('Package does not exists', 'error');
        return $this->package('global')->redirect('/admin/package/search');
    }

    // set active flag
    if (isset($config[$name]['active'])) {
        $config[$name]['active'] = true;
    } else {
        $config[$name]['active'] = true;
    }

    // update package config
    $this->package('global')->config('packages', $config);

    // redirect back
    $this->package('global')->flash('Package has been enabled', 'success');
    return $this->package('global')->redirect(
        '/admin/package/search?filter[active]=0'
    );
});

/**
 * Process Package Disable
 * 
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/package/disable/:name', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data
    if (!$request->getStage('type')) {
        $this->package('global')->flash('Invalid Request', 'error');
        return $this->package('global')->redirect('/admin/package/search');
    }
    
    // get package name
    $name = $request->getStage('name');

    // if it's a root package
    if ($request->getStage('type') === 'root') {
        $name = '/' . str_replace(':', '/', $name);
    } else {
        $name = str_replace(':', '/', $name);
    }

    // load package config
    $config = $this->package('global')->config('packages');

    // if package is not registered
    if (!isset($config[$name])) {
        $this->package('global')->flash('Package does not exists', 'error');
        return $this->package('global')->redirect('/admin/package/search');
    }

    // set active flag
    if (isset($config[$name]['active'])) {
        $config[$name]['active'] = false;
    }

    // update package config
    $this->package('global')->config('packages', $config);

    // redirect back
    $this->package('global')->flash('Package has been disabled', 'success');
    return $this->package('global')->redirect('/admin/package/search');
});

/**
 * Render Package Log
 * 
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/package/log/:log', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data

    // get package log path
    $log = $this->package('global')->path('config') . '/packages/%s.install.php';
    $log = sprintf($log, $request->getStage('log'));

    // if file exists
    if (file_exists($log)) {
        $data = [];

        try {
            // get log data
            $data = include($log);
        } catch(\Exception $e) {}

        // encode data as json
        try {
            // encode
            $data = json_encode($data);
        } catch(\Exception $e) {
            $data = '{}';
        };

        // send as response
        return $response->set('json', $data);
    }

    return $response->set('json', []);
});