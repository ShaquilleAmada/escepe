<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Classes;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\Services\RoomMatrixImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;

class RoomMatrixImportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_normalize_room_names(): void
    {
        $service = new RoomMatrixImportService();

        $this->assertEquals('Lab 16', $service->normalizeRoomName('L.16'));
        $this->assertEquals('Lab 1', $service->normalizeRoomName('L.1'));
        $this->assertEquals('Lab IPA', $service->normalizeRoomName('L.IPA'));
        $this->assertEquals('Ruang 22', $service->normalizeRoomName('22'));
        $this->assertEquals('Hall', $service->normalizeRoomName('HALL'));
        $this->assertEquals('OR1', $service->normalizeRoomName('OR1'));
        $this->assertEquals('Ruang Siniar', $service->normalizeRoomName('SINIAR'));
        $this->assertNull($service->normalizeRoomName('IST'));
        $this->assertNull($service->normalizeRoomName('-'));
    }
}

