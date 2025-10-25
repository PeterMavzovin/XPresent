<?php
namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function store(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
        ]);

        $userId = Auth::id();

        return DB::transaction(function () use ($validated, $service, $userId) {
            $start = $validated['start_time'];
            $end = date('H:i', strtotime($start) + ($service->duration + 30) * 60);

            $date = Carbon::parse($validated['date']);
            $dayOfWeek = $date->dayOfWeek; // 0 = воскресенье

            // 1️⃣ Проверяем воскресенье
            if ($dayOfWeek === 0) {
                abort(400, 'Бронирование в воскресенье недоступно.');
            }

            // 2️⃣ Проверяем рабочие часы
            if ($start < '10:00' || $end > '20:00') {
                abort(400, 'Бронирование доступно только с 10:00 до 20:00 по МСК.');
            }

            // 3️⃣ Проверяем пересечения с блокировкой строк (race condition-safe)
            $conflict = DB::table('bookings')
                ->where('service_id', $service->id)
                ->where('date', $validated['date'])
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_time', [$start, $end])
                        ->orWhereBetween('end_time', [$start, $end]);
                })
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                abort(409, 'Этот слот уже занят. Обновите страницу и выберите другой.');
            }

            // 4️⃣ Создаём запись
            $booking = Booking::create([
                'service_id' => $service->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'date' => $validated['date'],
                'start_time' => $start,
                'end_time' => $end,
                'user_id' => $userId,
            ]);

            return response()->json([
                'message' => 'Бронирование успешно создано!',
                'booking' => $booking,
            ]);
        });
    }

    public function availableSlots(Request $request, Service $service)
    {
        $date = Carbon::parse($request->date);
        $duration = $service->duration;
        $start = Carbon::parse($service->work_start);
        $end = Carbon::parse($service->work_end);

        $slots = [];
        while ($start->copy()->addMinutes($duration)->lte($end)) {
            $slotStart = $start->copy();
            $slotEnd = $start->copy()->addMinutes($duration);

            $exists = $service->bookings()
                ->where('date', $date->toDateString())
                ->whereBetween('start_time', [$slotStart, $slotEnd])
                ->exists();

            if (!$exists) {
                $slots[] = $slotStart->format('H:i');
            }

            $start->addMinutes($duration);
        }

        return response()->json($slots);
    }

}
