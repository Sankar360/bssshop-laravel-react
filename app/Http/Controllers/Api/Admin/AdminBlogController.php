<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminBlogController extends Controller
{
    /**
     * @var Blog
     */
    protected $blogModel;

    /**
     * AdminBlogController constructor.
     */
    public function __construct()
    {
        // Apply admin middleware
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->blogModel = new Blog();
    }

    /**
     * List blog posts with filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $status = $request->get('status', 'all');
        $perPage = $request->get('per_page', 20);

        $blogs = $this->blogModel->getBlogsWithFilters($search, $category, $status, $perPage);

        $stats = $this->blogModel->getBlogStats();
        $categories = $this->blogModel->getDistinctCategories();

        return response()->json([
            'success' => true,
            'data' => $blogs,
            'stats' => $stats,
            'categories' => $categories,
            'total_count' => $blogs->total() ?? count($blogs),
        ]);
    }

    /**
     * Show blog creation data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $categories = $this->blogModel->getDistinctCategories();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new blog post.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:255',
            'slug' => 'required|string|min:3|max:255|unique:blog,slug',
            'category' => 'required|string|min:3|max:100',
            'content' => 'required|string|min:10',
            'excerpt' => 'required|string|min:10|max:500',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Generate slug from title if not provided
        $slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->title);

        $data = [
            'title' => $request->title,
            'slug' => $slug,
            'category' => $request->category,
            'content' => $request->content,
            'excerpt' => $request->excerpt,
            'status' => $request->status,
            'author' => auth()->user()->name ?? 'Admin',
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'published_at' => $request->status === 'published' ? now() : null,
        ];

        // Handle featured image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('blog', 'public');
            $data['image'] = $path;
        }

        $blog = $this->blogModel->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Blog post created successfully',
            'data' => $blog,
        ], 201);
    }

    /**
     * Show blog post edit data.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $blog = $this->blogModel->find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog post not found',
            ], 404);
        }

        $categories = $this->blogModel->getDistinctCategories();

        return response()->json([
            'success' => true,
            'data' => $blog,
            'categories' => $categories,
        ]);
    }

    /**
     * Update a blog post.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $blog = $this->blogModel->find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog post not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:255',
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('blog', 'slug')->ignore($id),
            ],
            'category' => 'required|string|min:3|max:100',
            'content' => 'required|string|min:10',
            'excerpt' => 'required|string|min:10|max:500',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->title);

        $data = [
            'title' => $request->title,
            'slug' => $slug,
            'category' => $request->category,
            'content' => $request->content,
            'excerpt' => $request->excerpt,
            'status' => $request->status,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
        ];

        // Update published_at if status changed to published
        if ($request->status === 'published' && $blog->status !== 'published') {
            $data['published_at'] = now();
        }

        // Handle featured image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }

            $path = $request->file('image')->store('blog', 'public');
            $data['image'] = $path;
        }

        $blog->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Blog post updated successfully',
            'data' => $blog,
        ]);
    }

    /**
     * Delete a blog post.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $blog = $this->blogModel->find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog post not found',
            ], 404);
        }

        // Delete image
        if ($blog->image && Storage::disk('public')->exists($blog->image)) {
            Storage::disk('public')->delete($blog->image);
        }

        $blog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blog post deleted successfully',
        ]);
    }

    /**
     * Toggle blog post status (publish/draft).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        $blog = $this->blogModel->find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog post not found',
            ], 404);
        }

        $newStatus = $blog->status === 'published' ? 'draft' : 'published';
        $updateData = ['status' => $newStatus];

        // If publishing, set published_at
        if ($newStatus === 'published') {
            $updateData['published_at'] = now();
        }

        $blog->update($updateData);

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => 'Status updated successfully',
            'data' => $blog,
        ]);
    }

    /**
     * Export blog posts to CSV.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');

        if ($format !== 'csv') {
            return response()->json([
                'success' => false,
                'message' => 'Export format not supported',
            ], 400);
        }

        $blogs = $this->blogModel->orderBy('created_at', 'DESC')->get();

        $filename = 'blog_posts_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($blogs) {
            $output = fopen('php://output', 'w');

            // Headers
            fputcsv($output, ['ID', 'Title', 'Slug', 'Category', 'Author', 'Status', 'Views', 'Created Date']);

            // Data
            foreach ($blogs as $blog) {
                fputcsv($output, [
                    $blog->id,
                    $blog->title,
                    $blog->slug,
                    $blog->category,
                    $blog->author,
                    $blog->status,
                    $blog->views ?? 0,
                    $blog->created_at ? $blog->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get all blog categories for dropdown.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        $categories = $this->blogModel->getDistinctCategories();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get blog statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = $this->blogModel->getBlogStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Upload blog image (standalone endpoint).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->file('image')->store('blog', 'public');

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'data' => [
                'url' => Storage::url($path),
                'path' => $path,
            ],
        ]);
    }

    /**
     * Remove blog image.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeImage($id)
    {
        $blog = $this->blogModel->find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog post not found',
            ], 404);
        }

        if (!$blog->image) {
            return response()->json([
                'success' => false,
                'message' => 'No image to remove',
            ], 400);
        }

        if (Storage::disk('public')->exists($blog->image)) {
            Storage::disk('public')->delete($blog->image);
        }

        $blog->update(['image' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Image removed successfully',
        ]);
    }
}