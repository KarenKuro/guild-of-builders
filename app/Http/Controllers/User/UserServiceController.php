<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class UserServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Нужно отдать фронту список ($userServices) услуг конкретного пользователя вместе с услугами из админки
        // (таблицы: users, user_services, users)
        $user = $request->user();
        $userServices = UserService::where("user_id", $user->id)->with("service")->get();
        return Inertia::render('User/Services', ['userServices' => $userServices]); //, 'adminServices' => $adminServices]); // в метод render передать данные
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Нужно отдать фронту список, отобранных по языку услуг ($services) из админки
        // (таблица: services)
        $cookie = $request->header('cookie');
        $language = substr($cookie,5,2);
        if ($language == 'en') {
            $services = Service::select(['id', 'name'])->get();
        } else {
            $services = Service::select(['id','name_ru'])->get();
        }

        return Inertia::render('User/EditService', ['services' => $services]); // в метод render передать данные ($services)
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Валидация нужных полей (смотреть в таблице user_services) и запись в БД
        $data = $request->all();
        $userId = Auth::id();
        $service = Service::where('id', $data['service_id'])->first();
        
        if (!$service || strlen($service['name']) > 250 ||  preg_match('/^[a-zA-Zа-яА-ЯёЁ-] \s/', $service['name'])) { 
            return Redirect::back()->withErrors('Incorrect value in the "Service Name" field');
        }

        if (!$data['is_by_agreement'] &&  !$data['is_hourly_type'] && !$data['is_work_type']) {
            return Redirect::back()->withErrors('The "Payment type and amount" field must not be null');
        } elseif ($data['is_hourly_type'] && ($data['hourly_payment'] == null || (int)($data['hourly_payment']) <= 0)) {
            return Redirect::back()->withErrors('The field "hourly payment" is filled in incorrectly');
        } elseif ($data['is_work_type'] && ($data['work_payment'] == null ||(int)($data['work_payment']) <= 0)) {
            return Redirect::back()->withErrors('The field "work_payment" is filled in incorrectly');
        }

        $object = array('user_id'=> $userId,'service_id'=> $service['id'], 'is_active' => $data['is_active'],
            'work_payment'=> $data['work_payment'], 'hourly_payment'=> $data['hourly_payment'], 
            'is_work_type'=> $data['is_work_type'], 'is_hourly_type' => $data['is_hourly_type'],
            'is_by_agreement'=> $data['is_by_agreement'],
        ) ;


        $userService = new UserService();
        $userService->fill($object);
        $userService->save();

        return Redirect::route('user.services');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // не трогать!
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id, Request $request)
    {
        // Нужно отдать фронту список, отобранных по языку услуг ($services) из админки и услугу пользователя, которую мы обновляем
        // (таблица: services, user_services)
        $cookie = $request->header('cookie');
        $language = substr($cookie,5,2);
        if ($language == 'en') {
            $services = Service::select(['id', 'name'])->get();
            $userServices = UserService::findOrFail((int)$id);
        } else {
            $services = Service::select(['id','name_ru'])->get();
            $userServices = UserService::findOrFail((int)$id);

        }


        return Inertia::render('User/EditService', ['userService' => $userServices, 'services' => $services,  ]); // в метод render передать данные ($services, $userServices)
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Валидация нужных полей (смотреть в таблице user_services) и запись в БД
        $data = $request->all();
        $userId = Auth::id();
        $service = Service::where('id', $data['service_id'])->first();
        
        if (!$service || strlen($service['name']) > 250 ||  preg_match('/^[a-zA-Zа-яА-ЯёЁ-] \s/', $service['name'])) { 
            return Redirect::back()->withErrors('Incorrect value in the "Service Name" field');
        }

        if (!$data['is_by_agreement'] &&  !$data['is_hourly_type'] && !$data['is_work_type']) {
            return Redirect::back()->withErrors('The "Payment type and amount" field must not be null');
        } elseif ($data['is_hourly_type'] && ($data['hourly_payment'] == null || (int)($data['hourly_payment']) <= 0)) {
            return Redirect::back()->withErrors('The field "hourly payment" is filled in incorrectly');
        } elseif ($data['is_work_type'] && ($data['work_payment'] == null ||(int)($data['work_payment']) <= 0)) {
            return Redirect::back()->withErrors('The field "work_payment" is filled in incorrectly');
        }

        $object = array('user_id'=> $userId,'service_id'=> $service['id'], 'is_active' => $data['is_active'],
            'work_payment'=> $data['work_payment'], 'hourly_payment'=> $data['hourly_payment'], 
            'is_work_type'=> $data['is_work_type'], 'is_hourly_type' => $data['is_hourly_type'],
            'is_by_agreement'=> $data['is_by_agreement'],
        ) ;

        $oldObject = UserService::findOrFail($id);

        $oldObject->update($object);

        return Redirect::route('user.services');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Удаляем запись в бд
         $userService = UserService::find($id);

         if ($userService) {
             $userService->delete();
         } else {
             return response()->json(['message' => 'UserService not found'], 404);
         }

    }
}
