<?php
	class Telefonansage extends IPSModule {

		public function Create()
		{
			//Never delete this line!
            parent::Create();
            
            $this->RegisterPropertyInteger('VoIPInstanceID', 0);
            $this->RegisterPropertyInteger('TTSInstanceID', 0);
            $this->RegisterPropertyInteger('WaitForConnection', 20);

            $this->RegisterVariableString('PhoneNumber', $this->Translate('Phone Number'));
            $this->RegisterVariableString('Text', $this->Translate('Text'));

            $this->EnableAction('PhoneNumber');
            $this->EnableAction('Text');

            $this->RegisterScript('CallScript', $this->Translate('Start Call'), 'TA_StartCall(IPS_GetParent($_IPS["SELF"]));');

            $this->RegisterTimer('CheckConnectionTimer', 0, 'TA_CheckConnection(IPS_GetParent($_IPS["SELF"]));');
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
        }

        public function RequestAction($ident, $value) {
            $this->SetValue($ident, $value);
        }
        
        public function StartCall() {
            if (json_decode($this->GetBuffer('CallActive'))) {
                echo $this->Translate('The instance is already calling');
                return;
            }
            $id = VoIP_Connect($this->ReadPropertyInteger('VoIPInstanceID'), GetValue(47051));

            $this->SetBuffer('CallStart', json_encode(time()));
            $this->SetBuffer('CallID', json_encode($id));
            $this->SetTimerInterval('CheckConnectionTimer', 200);
        }

        public function CheckConnection() {
            $endCall = function() {
                $this->SetBuffer('CallActive', json_encode(false));
                $this->SetTimerInterval('CheckConnectionTimer', 0);
            };

            $c = VoIP_GetConnection($this->ReadPropertyInteger('VoIPInstance'), $id);
            if($c['Connected']) {
                // VoIP_Playwave() unterstützt ausschließlich WAV im Format: 16 Bit, 8000 Hz, Mono.
                VoIP_PlayWave($this->ReadPropertyInteger('TTSInstanceID'), $id, TTSAWSPOLLY_GenerateFile(50845, GetValue(39787)));
                $endCall();
            }
            else if ($this->GetBuffer('CallStart') < time() - $this->ReadPropertyInteger('WaitForConnection')) {
                $endCall();
            }
        }

	}