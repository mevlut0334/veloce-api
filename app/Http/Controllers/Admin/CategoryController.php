<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Contracts\CategoryServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    protected CategoryServiceInterface $categoryService;

    public function __construct(CategoryServiceInterface $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = $this->categoryService->getAdminCategories();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        try {
            $category = $this->categoryService->createCategory($request->validated());

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Kategori başarıyla oluşturuldu!');

        } catch (\Exception $e) {
            Log::error('Kategori oluşturma hatası (Controller)', [
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Kategori oluşturulurken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $category = $this->categoryService->getCategoryById($category->id);

        return view('admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        try {
            $this->categoryService->updateCategory($category, $request->validated());

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Kategori başarıyla güncellendi!');

        } catch (\Exception $e) {
            Log::error('Kategori güncelleme hatası (Controller)', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Kategori güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        try {
            $this->categoryService->deleteCategory($category);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Kategori başarıyla silindi!');

        } catch (\Exception $e) {
            Log::error('Kategori silme hatası (Controller)', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Kategori silinirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Toggle category active status
     */
    public function toggleActive(Category $category)
    {
        try {
            $this->categoryService->toggleCategoryStatus($category);

            $status = $category->fresh()->is_active ? 'aktif' : 'pasif';

            return redirect()
                ->back()
                ->with('success', "Kategori {$status} hale getirildi!");

        } catch (\Exception $e) {
            Log::error('Kategori durum değiştirme hatası (Controller)', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Durum değiştirilemedi: ' . $e->getMessage());
        }
    }

    /**
     * Toggle show on home status
     */
    public function toggleShowOnHome(Category $category)
    {
        try {
            $this->categoryService->toggleShowOnHome($category);

            $status = $category->fresh()->show_on_home ? 'gösteriliyor' : 'gizlendi';

            return redirect()
                ->back()
                ->with('success', "Kategori ana sayfada {$status}!");

        } catch (\Exception $e) {
            Log::error('Kategori ana sayfa durumu değiştirme hatası (Controller)', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Durum değiştirilemedi: ' . $e->getMessage());
        }
    }

    /**
     * Update category order (Reorder)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.order' => 'required|integer|min:0',
        ]);

        try {
            $this->categoryService->updateCategoryOrder($validated['categories']);

            return response()->json([
                'success' => true,
                'message' => 'Kategori sıralaması başarıyla güncellendi!'
            ]);

        } catch (\Exception $e) {
            Log::error('Kategori sıralama hatası (Controller)', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sıralama güncellenirken hata oluştu!'
            ], 500);
        }
    }
}
