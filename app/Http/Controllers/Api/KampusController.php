<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Libraries\ResponseLibrary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Kampus;

/**
 * ResponseLibrary untuk mempermudah response API.
 *
 * @author     Odenktools
 * @license    MIT
 * @package     \App\Models
 * @copyright  (c) 2019, Odenktools Technology
 */
class KampusController extends Controller
{
    public function __construct()
    {
        $this->model = new Kampus();
        $this->responseLib = new ResponseLibrary();
    }

    /**
     * Find data.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function getIndex(Request $request)
    {
        $nama_kampus = $request->input('nama_kampus');
        $kode_kampus = $request->input('kode_kampus');
        $alamat = $request->input('alamat');
        $limit = $request->input('limit') ? $request->input('limit') : 10;
        $sortBy = $request->input('sort') ? $request->input('sort') : 'updated_at';
        $orderBy = $request->input('order') ? $request->input('order') : 'DESC';
        $conditions = '1 = 1';

        if ($limit >= 20) {
            $limit = 10;
        }

        if (!empty($nama_kampus)) {
            $conditions .= " AND nama_kampus ILIKE '%$nama_kampus%'";
        }

        if (!empty($kode_kampus)) {
            $conditions .= " AND kode_kampus = '$kode_kampus'";
        }

        if (!empty($alamat)) {
            $conditions .= " AND alamat ILIKE '%$alamat%'";
        }

        $select = $this->model
            ->sql('*')
            ->whereRaw($conditions)
            ->limit($limit)
            ->orderBy($sortBy, $orderBy);

        $paging = array();
        if (!empty($select)) {
            $paginate = $select->paginate($limit);
            $paging['total'] = $paginate->total();
            $paging['per_page'] = $paginate->perPage();
            $paging['current_page'] = $paginate->currentPage();
            $paging['last_page'] = $paginate->lastPage();
            $paging['next_page_url'] = $paginate->nextPageUrl();
            $paging['prev_page_url'] = $paginate->previousPageUrl();
            $paging['from'] = $paginate->firstItem(); //(string)\Webpatser\Uuid\Uuid::generate(4);
            $paging['to'] = $paginate->lastItem();
            $results = $paginate->items();
            return response([
                'meta' => array('code' => 200, 'message' => 'Success'),
                'pageinfo' => $paging,
                'results' => $results,
            ], 200);
        }
    }

    /**
     * Insert data.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function postInsert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kampus' => 'required|unique:kampus|max:255',
            'no_telephone' => 'required|unique:kampus|max:150',
            'alamat' => 'required',
            'deskripsi' => 'required|string|max:255'
        ]);
        if ($validator->fails()) {
            return response($this->responseLib->validationFailResponse($validator->errors()->all()), 422);
        }
        DB::beginTransaction();
        try {
            $model = $this->model;
            $model->nama_kampus = $request->input('nama_kampus');
            $model->kode_kampus = Str::slug($request->input('nama_kampus'));
            $model->no_telephone = $request->input('no_telephone');
            $model->alamat = $request->input('alamat');
            $model->deskripsi = $request->input('deskripsi');
            $model->save();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            $code = $e->getCode();
            $message = $e->getMessage();
            return response($this->responseLib->failResponse(400, array("Error kode $code", $message)), 400);
        } catch (\Exception $e) {
            DB::rollback();
            $code = $e->getCode();
            $message = $e->getMessage();
            return response($this->responseLib->failResponse(400, array("Error kode $code", $message)), 400);
        }

        DB::commit();
        return response($this->responseLib->createResponse(200, array('success')), 200);
    }

    /**
     * Update data.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function putUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:255',
            'nama_kampus' => 'required|unique:kampus,nama_kampus,' . $request->input('id') . '|max:255',
            'alamat' => 'required',
            'no_telephone' => 'required',
            'deskripsi' => 'required|string|max:255'
        ]);
        if ($validator->fails()) {
            return response($this->responseLib->validationFailResponse($validator->errors()->all()), 422);
        }
        DB::beginTransaction();
        try {
            $model = $this->model->findOrFail($request->input('id'));
            $model->nama_kampus = $request->input('nama_kampus');
            $model->kode_kampus = Str::slug($request->input('nama_kampus'));
            $model->no_telephone = $request->input('no_telephone');
            $model->alamat = $request->input('alamat');
            $model->deskripsi = $request->input('deskripsi');
            $model->update();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            $code = $e->getCode();
            $message = $e->getMessage();
            return response($this->responseLib->failResponse(400, array("Error kode $code", $message)), 400);
        } catch (\Exception $e) {
            DB::rollback();
            return response($this->responseLib->failResponse(400, array($e->getMessage())), 400);
        }
        DB::commit();
        return response($this->responseLib->createResponse(200, array('success')), 200);
    }

    /**
     * Upload Image (multipart/form-data).
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function postImageKampus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:255',
            'foto_utama' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:6048',
            'foto_1' => 'image|mimes:jpeg,png,jpg,gif,svg|max:6048',
            'foto_2' => 'image|mimes:jpeg,png,jpg,gif,svg|max:6048',
            'foto_3' => 'image|mimes:jpeg,png,jpg,gif,svg|max:6048',
        ]);
        if ($validator->fails()) {
            return response($this->responseLib->validationFailResponse($validator->errors()->all()), 422);
        }
        $data = Kampus::find($request->input('id'));
        if (!$data) {
            return response($this->responseLib->failResponse(400, array('Data not found')), 400);
        }

        $imageData = array();

        DB::beginTransaction();

        try {
            if (!empty($request->file('foto_utama'))) {
                if ($request->file('foto_utama')->isValid()) {
                    $imageModel = new \App\Models\Image();
                    $path = $request->file('foto_utama')->store('images/kampus', 'public');
                    $uuid = (string)\Webpatser\Uuid\Uuid::generate(4);
                    $key_id = $imageModel->create(['id' => $uuid, 'image_url' => $path])->id;
                    $imageData[$key_id] = array();
                }
            }
            if (!empty($request->file('foto_1'))) {
                if ($request->file('foto_1')->isValid()) {
                    $imageModel = new \App\Models\Image();
                    $path = $request->file('foto_1')->store('images/kampus', 'public');
                    $uuid = (string)\Webpatser\Uuid\Uuid::generate(4);
                    $key_id = $imageModel->create(['id' => $uuid, 'image_url' => $path])->id;
                    $imageData[$key_id] = array();
                }
            }
            if (!empty($request->file('foto_2'))) {
                if ($request->file('foto_2')->isValid()) {
                    $imageModel = new \App\Models\Image();
                    $path = $request->file('foto_2')->store('images/kampus', 'public');
                    $uuid = (string)\Webpatser\Uuid\Uuid::generate(4);
                    $key_id = $imageModel->create(['id' => $uuid, 'image_url' => $path])->id;
                    $imageData[$key_id] = array();
                }
            }
            if (!empty($request->file('foto_3'))) {
                if ($request->file('foto_3')->isValid()) {
                    $imageModel = new \App\Models\Image();
                    $path = $request->file('foto_3')->store('images/kampus', 'public');
                    $uuid = (string)\Webpatser\Uuid\Uuid::generate(4);
                    $key_id = $imageModel->create(['id' => $uuid, 'image_url' => $path])->id;
                    $imageData[$key_id] = array();
                }
            }
            $data->kampus_images()->sync($imageData);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            $code = $e->getCode();
            $message = $e->getMessage();
            return response($this->responseLib->failResponse(400, array("Error kode $code", $message)), 400);
        } catch (\Exception $e) {
            DB::rollback();
            $code = $e->getCode();
            $message = $e->getMessage();
            return response($this->responseLib->failResponse(400, array("Error kode $code", $message)), 400);
        }
        DB::commit();
        return response($this->responseLib->createResponse(200, array("Success")), 200);
    }

    /**
     * Hapus records.
     *
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function deleteHapus($id)
    {
        DB::beginTransaction();
        try {
            $data = Kampus::find($id);
            if (!$data) {
                return response($this->responseLib->failResponse(400, array('Data not found')), 400);
            }
            $data->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            $code = $e->getCode();
            $message = $e->getMessage();
            return response($this->responseLib->failResponse(400, array("Error kode $code", $message)), 400);
        } catch (\Exception $e) {
            DB::rollback();
            return response($this->responseLib->failResponse(400, array($e->getMessage())), 400);
        }
        DB::commit();
        return response($this->responseLib->createResponse(200, array('success')), 200);
    }
}