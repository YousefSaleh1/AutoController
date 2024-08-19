<?php

namespace CodingPartners\AutoController\Traits;

trait ApiResponseTrait
{

    /**
     * Return a JSON Response For Auth mathod with token
     *
     * @param mixed $data the data return in the response
     * @param string $message the success message
     * @param string $token the user token
     * @param int $status the HTTP Status code
     * @return \Illuminate\Http\JsonResponse The JSON response
     */
    public function apiResponse($data,$token,$message,$status){
        $array = [
            'data' =>$data,
            'message' =>$message,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
        return response()->json($array,$status);
    }

    /**
     * Return a successful JSON Response
     *
     * @param mixed $data the data return in the response
     * @param string $message the success message
     * @param int $status the HTTP Status code
     * @return \Illuminate\Http\JsonResponse The JSON response
     */
    public function successResponse($data = null, $message = "Operation Done", $status = 200) {
        $array = [
            'status' => 'success',
            'data'=>$data,
            'message'=>trans($message)
        ];

        return response()->json($array, $status);
    }

    /**
     * Return a Error JSON Response
     *
     * @param mixed $data the data return in the response (errors or null)
     * @param string $message the error message
     * @param int $status the HTTP Status code
     * @return \Illuminate\Http\JsonResponse The JSON response
     */
    public function errorResponse($data = null, $message = "Operation Faild", $status) {
        $array = [
            'status' => 'error',
            'data'=>$data,
            'message'=>trans($message)
        ];
        return response()->json($array, $status);
    }

    /**
     * Return a paginated JSON Response
     *
     * @param $data the data that will be paginated
     * @param string $message the success message
     * @param int $status the HTTP Status code
     * @return \Illuminate\Http\JsonResponse The JSON response
     */
    public function resourcePaginated($data,$message = 'Operation Success',$status = 200){
        $paginator = $data->resource;
        $resourceData = $data->items();

        $array = [
            'status' => 'success',
            'message'=>trans($message),
            'data'=>$resourceData,
            'pagination' => [
                'total'        => $paginator->total(),
                'count'        => $paginator->count(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages'  => $paginator->lastPage(),
            ],
        ];
        return response()->json($array,$status);
    }
}
