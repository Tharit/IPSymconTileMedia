<?php


class TileMedia extends IPSModule
{
    const PROPERTIES = ['title', 'artist', 'volume', 'cover', 'state', 'position', 'duration'];
    const ACTIONS = ['play', 'pause', 'stop', 'next', 'prev'];

    public function Create()
    {
         // Never delete this line!
         parent::Create();

         foreach(self::PROPERTIES as $property) {
            $this->RegisterPropertyInteger($property, 0);
         }
         foreach(self::ACTIONS as $property) {
            $this->RegisterPropertyInteger($property, 0);
         }

         $this->SetVisualizationType(1);
    }

    public function ApplyChanges() {
        parent::ApplyChanges();

        foreach ($this->GetMessageList() as $senderID => $messageIDs) {
            foreach($messageIDs as $messageID) {
                $this->UnregisterMessage($senderID, $messageID);
            }
        }

        foreach(self::PROPERTIES as $property) {
            $this->RegisterMessage($this->ReadPropertyInteger($property), VM_UPDATE);
        }

        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
        foreach(self::PROPERTIES as $property) {
            $objectId = $this->ReadPropertyInteger($property);
            if ($SenderID === $objectId) {
                $this->UpdateVisualizationValue(json_encode([
                    $property => GetValue($objectId)
                ]));
                return;
            }
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if(in_array($Ident, self::ACTIONS)) {
            $script = $this->ReadPropertyInteger($Ident);
            if($script && @IPS_GetScript($script)) {
                IPS_RunScriptEx($script, [
                    "ACTION" => $Ident
                ]);
            }
        }
    }

    public function GetVisualizationTile() {
        $initialHandling = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ');</script>';

        $module = file_get_contents(__DIR__ . '/module.html');

        foreach(self::PROPERTIES as $property) {
            $objectId = $this->ReadPropertyInteger($property);
            $flag = $objectId !== 0;
            $module = str_replace("'{{HAS_" . strtoupper($property) . "}}'", $flag ? 'true' : 'false', $module);
        }
        foreach(self::ACTIONS as $property) {
            $objectId = $this->ReadPropertyInteger($property);
            $flag = $objectId !== 0;
            $module = str_replace("'{{HAS_" . strtoupper($property) . "}}'", $flag ? 'true' : 'false', $module);
        }
        
        return $module . $initialHandling;
    }

    private function GetFullUpdateMessage() {
        $array = [];
        foreach(self::PROPERTIES as $property) {
            $objectId = $this->ReadPropertyInteger($property);
            if(!$objectId) continue;
            $array[$property] = GetValue($objectId);
         }
        return json_encode($array);
    }
}
