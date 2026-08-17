<?php
        $exame = (object) array(
            'emergencia' => 0,
            'exame_id' => 1, // ECG emergencia e rapido
            'cliente_id' => 8 // Santa Casa de Salvador (emergencia)
        );
        $emergencia = $this->validaUrgenciaEmergencia($exame);
        if ($emergencia == Self::$LAUDO_EMERGENCIA) Log::Debug('passou 1');

        $exame = (object) array(
            'emergencia' => 0,
            'exame_id' => 1, // ECG emergencia e rapido
            'cliente_id' => 7 // Ubermed (rapido)
        );
        $emergencia = $this->validaUrgenciaEmergencia($exame);
        if ($emergencia == Self::$LAUDO_RAPIDO) Log::Debug('passou 2');

        $exame = (object) array(
            'emergencia' => 0,
            'exame_id' => 1, // ECG emergencia e rapido
            'cliente_id' => 1 // DLC (nao emergencia e nem rapido)
        );
        $emergencia = $this->validaUrgenciaEmergencia($exame);
        if ($emergencia == Self::$LAUDO_NORMAL) Log::Debug('passou 3');

        $exame = (object) array(
            'emergencia' => 1,
            'exame_id' => 1, // ECG emergencia e rapido
            'cliente_id' => 1 // DLC (nao emergencia e nem rapido)
        );
        $emergencia = $this->validaUrgenciaEmergencia($exame);
        if ($emergencia == Self::$LAUDO_RAPIDO) Log::Debug('passou 4');

        $exame = (object) array(
            'emergencia' => 1,
            'exame_id' => 3, // Espiro nao emergencia e nem rapido
            'cliente_id' => 1 // DLC (nao emergencia e nem rapido)
        );
        $emergencia = $this->validaUrgenciaEmergencia($exame);
        if ($emergencia == Self::$LAUDO_NORMAL) Log::Debug('passou 5');

        $empresa = (object) array('matriz' => 1);
        $exame = (object) array(
            'rg' => '1500479',
            'cpf' => null,
            'paciente' => 'Jose Lopes',
            'nascimento' => '1969-03-11 00:00:00',
            'sexo' => 'M'
        );
        $this->cadastraPaciente($exame, $empresa);
        LOG::Debug(">>>> FEITO");
