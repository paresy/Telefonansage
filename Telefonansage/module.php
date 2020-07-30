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

            $this->RegisterScript('CallScript', $this->Translate('Start Call'), '<?php TA_StartCall(IPS_GetParent($_IPS["SELF"]));');

            $this->RegisterTimer('CheckConnectionTimer', 0, 'TA_CheckConnection($_IPS["TARGET"]);');
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
            $this->StartCallEx($this->GetValue('PhoneNumber'), $this->GetValue('Text'));
        }
        
        public function StartCallEx(string $phoneNumber, string $text) {
            if (json_decode($this->GetBuffer('CallActive'))) {
                echo $this->Translate('The instance is already calling');
                return;
            }
            $id = VoIP_Connect($this->ReadPropertyInteger('VoIPInstanceID'), $phoneNumber);

            // if starting the call failed, do not start the timer
            if ($id === false) {
                return false;
            }
            
            $this->SetBuffer('CallStart', json_encode(time()));
            $this->SetBuffer('CallID', json_encode($id));
            $this->SetBuffer('Text', $text);
            $this->SetTimerInterval('CheckConnectionTimer', 200);

            return true;
        }

        public function CheckConnection() {
            $endCall = function() {
                $this->SetBuffer('CallActive', json_encode(false));
                $this->SetTimerInterval('CheckConnectionTimer', 0);
            };

            $id = json_decode($this->GetBuffer('CallID'));
            $c = VoIP_GetConnection($this->ReadPropertyInteger('VoIPInstanceID'), $id);
            if($c['Connected']) {
                // VoIP_Playwave() unterstützt ausschließlich WAV im Format: 16 Bit, 8000 Hz, Mono.
                VoIP_PlayWave($this->ReadPropertyInteger('VoIPInstanceID'), $id, TTSAWSPOLLY_GenerateFile($this->ReadPropertyInteger('TTSInstanceID'), $this->GetBuffer('Text')));
                $endCall();
            }
            else if (json_decode($this->GetBuffer('CallStart')) < (time() - $this->ReadPropertyInteger('WaitForConnection'))) {
                VOIP_Disconnect($this->ReadPropertyInteger('VoIPInstanceID'), $id);
                $endCall();
            }
        }

	}
