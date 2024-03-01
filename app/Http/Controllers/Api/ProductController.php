<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit') ?: 10;
        $sortby = $request->query('sortby') ?: 'desc';
        $orderby = $request->query('orderby') ?: 'id';

        $products = Product::orderBy($orderby, $sortby)->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreProductRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProductRequest $request)
    {
        if (auth()->user()->role == 2) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $data = $request->validated();
        $product = Product::create($data);

        return response()->json([
            "status" => "success",
            "message" => "Product created",
            "product" => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::find($id);

        if ($product === null) {
            return response()->json([
                "status" => "error",
                "message" => "Product not found!"
            ], 404);
        }
        return response()->json([
            "status" => "success",
            "product" => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProductRequest  $request
     * @param  \App\Models\Product  $Product
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductRequest $request, $id)
    {
        if (auth()->user()->role == 2) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $data = $request->validated();
        $product = Product::find($id);

        if ($product === null) {
            return response()->json([
                "status" => "error",
                "message" => "Product not found!"
            ], 404);
        }

        $product->update($data);

        return response()->json([
            "status" => "success",
            "message" => "Product updated",
            "product" => $product
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $Product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (auth()->user()->role == 2) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $product = Product::find($id);

        if ($product === null) {
            return response()->json([
                "status" => "error",
                "message" => "Product not found!"
            ], 404);
        }

        $product->delete();

        return response()->json([
            "status" => "success",
            "message" => "Product deleted"
        ], 201);
    }
}
