<?php
namespace App\Http\Controllers\Web;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use DB;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductsController extends Controller {

	use ValidatesRequests;

	public function __construct()
    {
        $this->middleware('auth:web')->except('list');
    }
/////// New
    public function buy(Request $request, Product $product)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (!$product->in_stock) {
            return redirect()->back()->with('error', 'This product is currently out of stock.');
        }

        // Mark product as out of stock after purchase
        $product->in_stock = false;
        $product->save();

        return redirect()->back()->with('success', 'Thank you for your purchase!');
    }

///////////////
	public function list(Request $request) {

		$query = Product::select("products.*");

		$query->when($request->keywords,
		fn($q)=> $q->where("name", "like", "%$request->keywords%"));

		$query->when($request->min_price,
		fn($q)=> $q->where("price", ">=", $request->min_price));

		$query->when($request->max_price, fn($q)=>
		$q->where("price", "<=", $request->max_price));

		$query->when($request->order_by,
		fn($q)=> $q->orderBy($request->order_by, $request->order_direction??"ASC"));

		$products = $query->get();

		return view('products.list', compact('products'));
	}

	public function edit(Request $request, Product $product = null) {

		if(!auth()->user()) return redirect('/');

		$product = $product??new Product();

		return view('products.edit', compact('product'));
	}

	public function save(Request $request, Product $product = null) {

		$this->validate($request, [
	        'code' => ['required', 'string', 'max:32'],
	        'name' => ['required', 'string', 'max:128'],
	        'model' => ['required', 'string', 'max:256'],
	        'description' => ['required', 'string', 'max:1024'],
	        'price' => ['required', 'numeric'],
	    ]);

		$product = $product??new Product();
		$product->fill($request->all());
		$product->save();

		return redirect()->route('products_list');
	}

	public function delete(Request $request, Product $product) {

		if(!auth()->user()->hasPermissionTo('delete_products')) abort(401);

		$product->delete();

		return redirect()->route('products_list');
	}

	/*
	public function __construct(){
		$this->middleware('role:Employee');  // Only allow employees to manage products
	}
	*/

	public function store(Request $request){
		$this->authorize('create', Product::class);  // Only employees can create products
		// Product creation logic
	}

	public function update(Request $request, Product $product){
		$this->authorize('update', $product);
		// Product update logic
	}

	public function destroy(Product $product){
		$this->authorize('delete', $product);
		// Product deletion logic
	}
}
