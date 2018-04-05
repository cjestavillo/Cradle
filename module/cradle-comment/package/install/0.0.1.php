<?php //-->

use Cradle\Http\Request;
use Cradle\Http\Response;

cradle(function() {
    //setup a new RnR
    $request = Request::i()->load();
    $response = Response::i()->load();

    //setup result counters
    $errors = [];
    $processed = [];

    //scan through each file
    foreach (scandir(__DIR__ . '/../schema') as $file) {
        //if it's not a php file
        if (substr($file, -4) !== '.php') {
            //skip
            continue;
        }

        //get the schema data
        $data = include sprintf('%s/../schema/%s', __DIR__, $file);

        //if no name
        if (!isset($data['name'])) {
            //skip
            continue;
        }

        //set the data
        $request->setStage($data);

        //----------------------------//
        // 1. Prepare Data
        //if detail has no value make it null
        if ($request->hasStage('detail')
            && !$request->getStage('detail')
        ) {
            $request->setStage('detail', null);
        }

        //if fields has no value make it an array
        if ($request->hasStage('fields')
            && !$request->getStage('fields')
        ) {
            $request->setStage('fields', []);
        }

        //if validation has no value make it an array
        if ($request->hasStage('validation')
            && !$request->getStage('validation')
        ) {
            $request->setStage('validation', []);
        }

        //----------------------------//
        // 2. Process Request
        //now trigger
        $this->trigger('system-schema-create', $request, $response);

        //----------------------------//
        // 3. Interpret Results
        //if the event returned an error
        if ($response->isError()) {
            //collect all the errors
            $errors[$data['name']] = $response->getValidation();
            continue;
        }

        $processed[] = $data['name'];
    }

    if (!empty($errors)) {
        $this->getResponse()->set('json', 'validation', $errors);
    }

    $this->getResponse()->setResults('schemas', $processed);
});
