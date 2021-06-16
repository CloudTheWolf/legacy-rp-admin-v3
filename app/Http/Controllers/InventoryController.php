<?php

namespace App\Http\Controllers;

use App\Character;
use App\Http\Resources\InventoryLogResource;
use App\Inventory;
use App\Log;
use App\Player;
use App\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    /**
     * Display a inventory logs related to a character.
     *
     * @param Character $character
     * @param Request $request
     * @return Response
     */
    public function character(Character $character, Request $request): Response
    {
        $inventories = [
            'details LIKE \'%character-' . $character->character_id . ':%\'',
            'details LIKE \'%locker-police-' . $character->character_id . ':%\'',
            'details LIKE \'%locker-mechanic-' . $character->character_id . ':%\'',
            'details LIKE \'%locker-ems-' . $character->character_id . ':%\'',
        ];

        return $this->searchInventories($request, $inventories);
    }

    /**
     * Display a inventory logs related to a vehicle.
     *
     * @param Vehicle $vehicle
     * @param Request $request
     * @return Response
     */
    public function vehicle(Vehicle $vehicle, Request $request): Response
    {
        $inventories = [
            'details REGEXP \'trunk-([0-9]{1,3}-)?' . $vehicle->plate . ':\'',
            'details REGEXP \'trunk-([0-9]{1,3}-)?' . $vehicle->vehicle_id . ':\'',
            'details REGEXP \'glovebox-([0-9]{1,3}-)?' . $vehicle->plate . ':\'',
            'details REGEXP \'glovebox-([0-9]{1,3}-)?' . $vehicle->vehicle_id . ':\'',
        ];

        return $this->searchInventories($request, $inventories);
    }

    /**
     * @param Request $request
     * @param array $inventories
     * @return Response
     */
    private function searchInventories(Request $request, array $inventories): Response
    {
        $start = round(microtime(true) * 1000);

        $query = Log::query()->orderByDesc('timestamp');

        $query->fromSub('SELECT * FROM user_logs WHERE action=\'Item Moved\'', 'logs');

        $page = Paginator::resolveCurrentPage('page');

        $logs = InventoryLogResource::collection([]);
        if (!empty($inventories)) {
            foreach ($inventories as $inventory) {
                $query->orWhereRaw($inventory);
            }

            $query->select(['id', 'identifier', 'action', 'details', 'metadata', 'timestamp']);

            $query->limit(15)->offset(($page - 1) * 15);

            $logs = InventoryLogResource::collection($query->get());
        }

        $url = $_SERVER['REQUEST_URI'];
        if (Str::contains($url, '?')) {
            $url .= '&';
        } else {
            $url .= '?';
        }
        $next = $url . 'page=' . ($page + 1);
        $prev = $url . 'page=' . ($page - 1);

        $end = round(microtime(true) * 1000);

        return Inertia::render('Inventories/Index', [
            'logs'      => $logs,
            'playerMap' => Player::fetchSteamPlayerNameMap($logs->toArray($request), 'steamIdentifier'),
            'links'     => [
                'next' => $next,
                'prev' => $prev,
            ],
            'time'      => $end - $start,
            'page'      => $page,
        ]);
    }

    /**
     * Display information related to an inventory.
     *
     * @param string $inventory
     * @param Request $request
     * @return Response
     */
    public function show(string $inventory, Request $request): Response
    {
        return Inertia::render('Inventories/Show', [
            'inventory' => Inventory::parseDescriptor($inventory)->get(),
        ]);
    }

}
