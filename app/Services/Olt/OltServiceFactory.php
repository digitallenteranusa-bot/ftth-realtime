<?php
namespace App\Services\Olt;

use App\Models\Olt;

class OltServiceFactory
{
    public static function make(Olt $olt): OltDriverInterface
    {
        return match ($olt->vendor) {
            'zte' => new ZteOltDriver($olt),
            'huawei' => new HuaweiOltDriver($olt),
            'fiberhome' => new FiberHomeOltDriver($olt),
            'hiso', 'hioso', 'c-data', 'vsol', 'bdcom' => new HisoOltDriver($olt),
            default => new GenericOltDriver($olt),
        };
    }
}
