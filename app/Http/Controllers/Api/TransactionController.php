<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
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

        if (auth()->user()->role == 2) {
            $transactions = Transaction::where('user_id', auth()->user()->id)->orderBy($orderby, $sortby)->paginate($limit);
        } else {
            $transactions = Transaction::orderBy($orderby, $sortby)->paginate($limit);
        }

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreTransactionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTransactionRequest $request)
    {
        if (auth()->user()->role == 1) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();

            $product = Product::find($data['id']);

            if ($product === null) {
                return response()->json([
                    "status" => "error",
                    "message" => "Product not found!"
                ], 404);
            }

            if ($product->quantity < $data['quantity']) {
                return response()->json([
                    "status" => "error",
                    "message" => ($product->quantity == 0) ? "Stok barang kosong" : "Jumlah pembelian melebihi stok!"
                ], 400);
            }

            $subTotalProduct = $product->price * $data['quantity'];
            $tax = $subTotalProduct * (10 / 100);
            $admin_fee = ($subTotalProduct + $tax) * (5 / 100);

            $transactionDetail = [
                'user_id' => auth()->user()->id,
                'product_id' => $product->id,
                'price' => $product->price,
                'quantity' => $data['quantity'],
                'admin_fee' => $admin_fee,
                'tax' => $tax,
                'total' => $subTotalProduct + $tax + $admin_fee
            ];

            // Deduct Product Quantity
            $product->quantity = $product->quantity - $data['quantity'];
            $product->save();

            // Save Transactions
            $transaction = Transaction::create($transactionDetail);

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Transaction created",
                "transaction" => $transaction
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'An error occured. Please try again later',
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (auth()->user()->role == 2) {
            $transaction = Transaction::where('user_id', auth()->user()->id)->find($id);
        } else {
            $transaction = Transaction::find($id);
        }

        if ($transaction === null) {
            return response()->json([
                "status" => "error",
                "message" => "Transaction not found!"
            ], 404);
        }
        return response()->json([
            "status" => "success",
            "transaction" => $transaction
        ]);
    }
}
