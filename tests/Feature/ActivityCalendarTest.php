<?php

use App\Models\Crop;
use App\Models\User;
use Carbon\CarbonImmutable;

function createActivityCalendarCrop(
    User $user,
    string $landName = 'Lahan Kalender',
    string $cropName = 'Tanaman Kalender',
): Crop {
    $land = $user->lands()->create([
        'name' => $landName,
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);

    return $land->crops()->create([
        'name' => $cropName,
        'planting_date' => today(),
    ]);
}

it('returns only activities within the requested month across a year boundary', function () {
    $this->travelTo(CarbonImmutable::parse('2026-12-31 18:00:00', 'Asia/Jakarta'));
    $user = User::factory()->create();
    $crop = createActivityCalendarCrop($user);

    $crop->activities()->create([
        'activity_type' => 'Pemupukan Desember',
        'scheduled_date' => '2026-12-31',
        'status' => 'belum_dilakukan',
    ]);
    $januaryActivity = $crop->activities()->create([
        'activity_type' => 'Penyiraman Januari',
        'scheduled_date' => '2027-01-01',
        'status' => 'belum_dilakukan',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('activities.calendar', ['month' => '2027-01']));

    $response
        ->assertOk()
        ->assertJsonPath('month', '2027-01')
        ->assertJsonPath('selected_date', '2027-01-01')
        ->assertJsonCount(1, 'calendar_activities')
        ->assertJsonPath('calendar_activities.0.id', $januaryActivity->id);
});

it('renders the calendar page with owned filter options for browser requests', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 12:00:00', 'Asia/Jakarta'));
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedCrop = createActivityCalendarCrop($user, 'Lahan Saya', 'Tanaman Saya');
    $otherCrop = createActivityCalendarCrop($otherUser, 'Lahan Rahasia', 'Tanaman Rahasia');
    $ownedCrop->activities()->create([
        'activity_type' => 'Penyiraman',
        'scheduled_date' => '2026-10-18',
        'status' => 'belum_dilakukan',
    ]);
    $otherCrop->activities()->create([
        'activity_type' => 'Aktivitas Rahasia',
        'scheduled_date' => '2026-10-18',
        'status' => 'belum_dilakukan',
    ]);

    $response = $this->actingAs($user)->get(route('activities.calendar', [
        'month' => '2026-10',
        'date' => '2026-10-18',
        'land_id' => $ownedCrop->land_id,
        'status' => 'belum_dilakukan',
    ]));

    $response
        ->assertOk()
        ->assertViewIs('activities.calendar')
        ->assertSee('Kalender Kegiatan')
        ->assertSee('Penyiraman')
        ->assertSee('Tanaman Saya')
        ->assertDontSee('Tanaman Rahasia')
        ->assertSee(route('activities.calendar', [
            'land_id' => $ownedCrop->land_id,
            'status' => 'belum_dilakukan',
            'month' => '2026-10',
            'date' => '2026-10-18',
        ]))
        ->assertSee(route('activities.calendar', [
            'land_id' => $ownedCrop->land_id,
            'status' => 'belum_dilakukan',
            'month' => '2026-09',
        ]))
        ->assertSee(route('activities.calendar', [
            'land_id' => $ownedCrop->land_id,
            'status' => 'belum_dilakukan',
            'month' => '2026-11',
        ]));

    $response->assertViewHas('crops', fn ($crops) => $crops->modelKeys() === [$ownedCrop->id]);

    $this->get(route('activities.index'))
        ->assertOk()
        ->assertSee('Kalender')
        ->assertSee(route('activities.create'), false);
});

it('defaults the calendar to today in Jakarta when UTC is on the previous date', function () {
    $this->travelTo(CarbonImmutable::parse('2026-12-31 17:30:00', 'UTC'));
    $user = User::factory()->create();
    $crop = createActivityCalendarCrop($user);

    $yesterdayActivity = $crop->activities()->create([
        'activity_type' => 'Kegiatan Kemarin',
        'scheduled_date' => '2026-12-31',
        'status' => 'belum_dilakukan',
    ]);
    $todayActivity = $crop->activities()->create([
        'activity_type' => 'Kegiatan Hari Ini',
        'scheduled_date' => '2027-01-01',
        'status' => 'belum_dilakukan',
    ]);

    $response = $this->actingAs($user)->getJson(route('activities.calendar'));

    $response
        ->assertOk()
        ->assertJsonPath('month', '2027-01')
        ->assertJsonPath('selected_date', '2027-01-01')
        ->assertJsonPath('today', '2027-01-01')
        ->assertJsonPath('tomorrow', '2027-01-02')
        ->assertJsonCount(1, 'calendar_activities')
        ->assertJsonPath('calendar_activities.0.id', $todayActivity->id)
        ->assertJsonPath('today_activities.0.id', $todayActivity->id)
        ->assertJsonCount(1, 'overdue_activities')
        ->assertJsonPath('overdue_activities.0.id', $yesterdayActivity->id);
});

it('groups today tomorrow and overdue activities using Jakarta dates without changing statuses', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 00:30:00', 'Asia/Jakarta'));
    $user = User::factory()->create();
    $crop = createActivityCalendarCrop($user);

    $todayActivity = $crop->activities()->create([
        'activity_type' => 'Kegiatan Hari Ini',
        'scheduled_date' => '2026-10-18',
        'scheduled_time' => '08:00',
        'status' => 'belum_dilakukan',
    ]);
    $tomorrowActivity = $crop->activities()->create([
        'activity_type' => 'Kegiatan Besok',
        'scheduled_date' => '2026-10-19',
        'status' => 'belum_dilakukan',
    ]);
    $overdueActivity = $crop->activities()->create([
        'activity_type' => 'Kegiatan Terlambat',
        'scheduled_date' => '2026-10-17',
        'status' => 'belum_dilakukan',
    ]);
    $completedActivity = $crop->activities()->create([
        'activity_type' => 'Kegiatan Selesai',
        'scheduled_date' => '2026-10-16',
        'status' => 'selesai',
    ]);
    $skippedActivity = $crop->activities()->create([
        'activity_type' => 'Kegiatan Dilewati',
        'scheduled_date' => '2026-10-15',
        'status' => 'dilewati',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('activities.calendar', ['date' => '2026-10-18']));

    $response
        ->assertOk()
        ->assertJsonPath('today', '2026-10-18')
        ->assertJsonPath('tomorrow', '2026-10-19')
        ->assertJsonPath('selected_date', '2026-10-18')
        ->assertJsonCount(1, 'selected_date_activities')
        ->assertJsonPath('selected_date_activities.0.id', $todayActivity->id)
        ->assertJsonPath('today_activities.0.id', $todayActivity->id)
        ->assertJsonPath('tomorrow_activities.0.id', $tomorrowActivity->id)
        ->assertJsonCount(1, 'overdue_activities')
        ->assertJsonPath('overdue_activities.0.id', $overdueActivity->id);

    expect($overdueActivity->fresh()->status)->toBe('belum_dilakukan');
    expect($completedActivity->fresh()->status)->toBe('selesai');
    expect($skippedActivity->fresh()->status)->toBe('dilewati');
});

it('filters calendar and reminder lists by owned land crop and status', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 12:00:00', 'Asia/Jakarta'));
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $firstCrop = createActivityCalendarCrop($user, 'Lahan Pertama', 'Tanaman Pertama');
    $secondCrop = createActivityCalendarCrop($user, 'Lahan Kedua', 'Tanaman Kedua');
    $otherCrop = createActivityCalendarCrop($otherUser, 'Lahan Rahasia', 'Tanaman Rahasia');
    $firstActivity = $firstCrop->activities()->create([
        'activity_type' => 'Penyiraman Pertama',
        'scheduled_date' => '2026-10-18',
        'status' => 'belum_dilakukan',
    ]);
    $secondActivity = $secondCrop->activities()->create([
        'activity_type' => 'Pemupukan Kedua',
        'scheduled_date' => '2026-10-18',
        'status' => 'selesai',
    ]);
    $otherActivity = $otherCrop->activities()->create([
        'activity_type' => 'Aktivitas Rahasia',
        'scheduled_date' => '2026-10-18',
        'status' => 'belum_dilakukan',
    ]);

    $response = $this->actingAs($user)->getJson(route('activities.calendar', [
        'month' => '2026-10',
        'land_id' => $firstCrop->land_id,
        'crop_id' => $firstCrop->id,
        'status' => 'belum_dilakukan',
    ]));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'calendar_activities')
        ->assertJsonPath('calendar_activities.0.id', $firstActivity->id)
        ->assertJsonPath('today_activities.0.id', $firstActivity->id)
        ->assertJsonMissing(['id' => $secondActivity->id])
        ->assertJsonMissing(['id' => $otherActivity->id])
        ->assertJsonPath('calendar_activities.0.crop.land.name', 'Lahan Pertama');
});

it('applies land crop and status filters independently', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 12:00:00', 'Asia/Jakarta'));
    $user = User::factory()->create();
    $firstCrop = createActivityCalendarCrop($user, 'Lahan Pertama', 'Tanaman Pertama');
    $secondCrop = createActivityCalendarCrop($user, 'Lahan Kedua', 'Tanaman Kedua');
    $firstActivity = $firstCrop->activities()->create([
        'activity_type' => 'Penyiraman Pertama',
        'scheduled_date' => '2026-10-18',
        'status' => 'belum_dilakukan',
    ]);
    $secondActivity = $secondCrop->activities()->create([
        'activity_type' => 'Pemupukan Kedua',
        'scheduled_date' => '2026-10-18',
        'status' => 'selesai',
    ]);

    $this->actingAs($user)
        ->getJson(route('activities.calendar', ['land_id' => $firstCrop->land_id]))
        ->assertOk()
        ->assertJsonCount(1, 'calendar_activities')
        ->assertJsonPath('calendar_activities.0.id', $firstActivity->id);

    $this->getJson(route('activities.calendar', ['crop_id' => $secondCrop->id]))
        ->assertOk()
        ->assertJsonCount(1, 'calendar_activities')
        ->assertJsonPath('calendar_activities.0.id', $secondActivity->id);

    $this->getJson(route('activities.calendar', ['status' => 'selesai']))
        ->assertOk()
        ->assertJsonCount(1, 'calendar_activities')
        ->assertJsonPath('calendar_activities.0.id', $secondActivity->id)
        ->assertJsonPath('today_activities.0.id', $secondActivity->id);
});

it('rejects invalid month date and status filters', function (array $filters, string $field) {
    $user = User::factory()->create();
    createActivityCalendarCrop($user);

    $this->actingAs($user)
        ->getJson(route('activities.calendar', $filters))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'invalid calendar month' => [['month' => '2026-13'], 'month'],
    'malformed selected date' => [['date' => '18-10-2026'], 'date'],
    'selected date outside month' => [['month' => '2026-10', 'date' => '2026-11-01'], 'date'],
    'non-integer land filter' => [['land_id' => 'bukan-id'], 'land_id'],
    'non-integer crop filter' => [['crop_id' => 'bukan-id'], 'crop_id'],
    'unknown status' => [['status' => 'terlambat'], 'status'],
]);

it('rejects land and crop filters that do not belong to the signed in user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedCrop = createActivityCalendarCrop($user);
    $otherCrop = createActivityCalendarCrop($otherUser, 'Lahan Orang Lain', 'Tanaman Orang Lain');

    $this->actingAs($user)
        ->getJson(route('activities.calendar', ['land_id' => $otherCrop->land_id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('land_id');

    $this->getJson(route('activities.calendar', ['crop_id' => $otherCrop->id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('crop_id');

    $this->getJson(route('activities.calendar', [
        'land_id' => $ownedCrop->land_id,
        'crop_id' => $otherCrop->id,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('crop_id');
});

it('does not return another users activities in any calendar grouping', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 12:00:00', 'Asia/Jakarta'));
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedCrop = createActivityCalendarCrop($user);
    $otherCrop = createActivityCalendarCrop($otherUser, 'Lahan Rahasia', 'Tanaman Rahasia');
    $ownedActivity = $ownedCrop->activities()->create([
        'activity_type' => 'Kegiatan Milik Sendiri',
        'scheduled_date' => '2026-10-18',
        'status' => 'belum_dilakukan',
    ]);
    $otherTodayActivity = $otherCrop->activities()->create([
        'activity_type' => 'Rahasia Hari Ini',
        'scheduled_date' => '2026-10-18',
        'status' => 'belum_dilakukan',
    ]);
    $otherTomorrowActivity = $otherCrop->activities()->create([
        'activity_type' => 'Rahasia Besok',
        'scheduled_date' => '2026-10-19',
        'status' => 'belum_dilakukan',
    ]);
    $otherOverdueActivity = $otherCrop->activities()->create([
        'activity_type' => 'Rahasia',
        'scheduled_date' => '2026-10-17',
        'status' => 'belum_dilakukan',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('activities.calendar'));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'calendar_activities')
        ->assertJsonPath('calendar_activities.0.id', $ownedActivity->id)
        ->assertJsonPath('today_activities.0.id', $ownedActivity->id)
        ->assertJsonCount(1, 'today_activities')
        ->assertJsonCount(0, 'tomorrow_activities')
        ->assertJsonCount(0, 'overdue_activities')
        ->assertJsonMissing(['id' => $otherTodayActivity->id])
        ->assertJsonMissing(['id' => $otherTomorrowActivity->id])
        ->assertJsonMissing(['id' => $otherOverdueActivity->id]);
});

it('redirects guests from the calendar to login', function () {
    $this->get(route('activities.calendar'))
        ->assertRedirect(route('login'));
});
