<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018  Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Seat\Web\Http\Controllers\Corporation;

use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Services\Repositories\Corporation\Members;
use Seat\Web\Http\Controllers\Controller;
use Yajra\DataTables\DataTables;

class TrackingController extends Controller
{
    use Members;

    /**
     * @param $corporation_id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getTracking(int $corporation_id)
    {

        return view('web::corporation.tracking');
    }

    /**
     * @param int $corporation_id
     *
     * @return mixed
     * @throws \Exception
     */
    public function getMemberTracking(int $corporation_id)
    {

        $selected_status = collect(request('selected_refresh_token_status'));

        $tracking = $this->getCorporationMemberTracking($corporation_id);

        if($selected_status->contains('valid_token') && ! $selected_status->contains('invalid_token'))
            $tracking->has('user.refresh_token');

        if($selected_status->contains('invalid_token') && ! $selected_status->contains('valid_token'))
            $tracking->doesntHave('user')->orDoesntHave('user.refresh_token');

        return DataTables::of($tracking)
            ->editColumn('character_id', function ($row) {

                $character_id = $row->character_id;

                $character = CharacterInfo::find($row->character_id) ?: $row->character_id;

                return view('web::partials.character', compact('character', 'character_id'));
            })
            ->addColumn('location', function ($row) {
                return view('web::corporation.partials.location', compact('row'));
            })

            ->addColumn('refresh_token', function ($row) {

                $refresh_token = false;

                if(! is_null(optional($row->user)->refresh_token))
                    $refresh_token = true;

                return view('web::corporation.partials.refresh-token', compact('refresh_token'));
            })
            ->addColumn('main_character', function ($row) {

                $character_id = $row->character_id;

                if(is_null($row->user))
                    return '';

                $main_character_id = $character_id;

                if (! is_null($row->user->group) && ! is_null(optional($row->user->group)->main_character_id))
                    $main_character_id = $row->user->group->main_character_id;

                $character = CharacterInfo::find($main_character_id) ?: $main_character_id;

                return view('web::partials.character', compact('character', 'character_id'));
            })
            ->rawColumns(['character_id', 'main_character', 'refresh_token', 'location'])
            ->make(true);

    }
}
