<?php

namespace App\Http\Controllers\Admin\CMS;

use App\Client\FileUpload\FileUploaderInterface;
use App\Http\Controllers\Controller;
use App\Repositories\Media\MediaRepository;
use App\Repositories\NocUser\NocApplicationRepository;
use App\Repositories\User\UserRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class NocController extends Controller
{
    protected $nocApplicationRepository, $userRepository;
    protected $fileUploader;
    protected $mediaRepository;

    public function __construct(NocApplicationRepository $nocApplicationRepository, FileUploaderInterface $fileUploader, MediaRepository $mediaRepository, UserRepository $userRepository)
    {
        $this->nocApplicationRepository = $nocApplicationRepository;
        $this->fileUploader = $fileUploader;
        $this->mediaRepository = $mediaRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $noc = $this->nocApplicationRepository->getPaginatedList($request, 'noc');
        $statuses = ['pending', 'approved', 'rejected']; // Example statuses

        // Fetch counts from repository
        $statusCountsData = $this->nocApplicationRepository->getStatusCounts($statuses);

        // Initialize result with zero counts
        $statusCounts = array_fill_keys($statuses, 0);

        // Merge database results into predefined statuses
        foreach ($statusCountsData as $status => $data) {
            $statusCounts[$status] = $data['count'];
        }

        $goodStanding = $this->nocApplicationRepository->getPaginatedListGoodStanding($request, 'good_standing');

        return view('admin.pages.cms.noc.index', compact('noc', 'request', 'statusCounts', 'goodStanding'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $applicant = $this->nocApplicationRepository->findOrFail($id);
        return view('admin.pages.cms.noc.show', compact('applicant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->all();
        try {
            DB::beginTransaction();
            $data['status'] = 'rejected'; // Default status
            $data['remarks'] = $data['remarks'] ?? '';
            $banner = $this->nocApplicationRepository->update($id, $data);
            if ($banner === false) {
                session()->flash('danger', 'Oops! Something went wrong.');
                return redirect()->back()->withInput();
            }

            DB::commit();
            session()->flash('success', 'Noc Form has been updated successfully.');

            $nocData = $this->nocApplicationRepository->findOrFail($id);
            if ($nocData->good_standing) {
                return redirect()->route('good-standing-main.index');
            }
            return redirect()->route('noc-main.index');
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Oops! Something went wrong.' . $e);
            return redirect()->back()->withInput();
        }
    }

    public function approve(Request $request, string $id)
    {
        $data = $request->all();

        try {
            DB::beginTransaction();

            $nocData = $this->nocApplicationRepository->findOrFail($id);

            $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $pdf_file_name = 'noc_' . $uuid . '.pdf';
            $pdf_path = storage_path("app/public/noc/{$nocData->id}");
            $pdf_link = "noc/{$nocData->id}/{$pdf_file_name}";
            $qr_url = "storage/{$pdf_link}";

            if (!file_exists($pdf_path)) {
                mkdir($pdf_path, 0755, true);
            }

            $url = url($qr_url);

            // Define path and filename
            $nocId = $nocData->id;
            $qrFileName = 'qr_' . $nocId . '.svg';
            $qrPath = public_path('storage/noc/' . $nocId . '/' . $qrFileName);

            // Ensure directory exists
            if (!file_exists(dirname($qrPath))) {
                mkdir(dirname($qrPath), 0755, true);
            }

            // Generate and save SVG
            file_put_contents($qrPath, QrCode::format('svg')->size(200)->generate($url));

            // Pass the relative path to the view
            $qrCodePath = 'storage/noc/' . $nocId . '/' . $qrFileName;

            // dd($qrCodeBase64);
            $view = $nocData->good_standing ? 'admin.pages.cms.noc.good_standing' : 'admin.pages.cms.noc.noc_registration';
            $data = [
                'nocData' => $nocData,
                'currentDate' => Carbon::now()->format($nocData->good_standing ? 'd-m-Y' : 'Y-m-d'),
                'qrCode' => $qrCodePath,
            ];
            if ($nocData->good_standing) {
                $data['dob'] = Carbon::parse($nocData->dob_ad)->format('d M Y');
            }

            Pdf::loadView($view, $data)->save("{$pdf_path}/{$pdf_file_name}");

            $updateData = [
                'uuid' => $uuid,
                'status' => 'approved',
                'pdf_link' => $pdf_link,
            ];

            $banner = $this->nocApplicationRepository->update($id, $updateData);
            if ($banner === false) {
                DB::rollBack();
                session()->flash('danger', 'Oops! Something went wrong.');
                return redirect()->back()->withInput();
            }

            DB::commit();
            session()->flash('success', 'NOC Form has been submitted successfully.');

            return redirect()->route($nocData->good_standing ? 'good-standing-main.index' : 'noc-main.index');
        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            session()->flash('error', 'Oops! Something went wrong.');
            return redirect()->back()->withInput();
        }
    }

    public function storeData(Request $request)
    {
        $data = $request->all();
        $nocData = $this->nocApplicationRepository->findOrFail($data['applicant_id']);
        $banner = $this->nocApplicationRepository->update($nocData->id, $data);
        if ($banner === false) {
            session()->flash('danger', 'Oops! Something went wrong.');
            return redirect()->back()->withInput();
        }
        if ($nocData->good_standing) {
            return redirect()->route('good-standing-main.index');
        }
        return redirect()->route('noc-main.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function downloadImages($userId)
    {
        // Path to the folder containing images (adjust the folder structure as needed)
        $folderPath = storage_path("app/public/noc/{$userId}/"); // This is the folder path where the images are stored

        // Check if the folder exists
        if (!File::exists($folderPath)) {
            return response()->json(['error' => 'Folder not found.'], 404);
        }

        // Get all image files in the folder
        $images = File::files($folderPath);

        // Check if there are images in the folder
        if (count($images) === 0) {
            return response()->json(['error' => 'No images found.'], 404);
        }

        // Prepare the images data for the PDF (array of image paths)
        $imagePaths = [];
        foreach ($images as $image) {
            // You can store the relative path or full URL depending on your requirement
            $imagePaths[] = asset('storage/noc/' . $userId . '/' . basename($image));
        }

        $nocData = $applicant = $this->nocApplicationRepository->findOrFail($userId);

        $url = url($nocData->pdf_link);
        $qrCode = QrCode::size(100)->generate($url);
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCode);
        $dobFormatted = Carbon::parse($nocData->dob_ad)->format('d M Y');

        if ($nocData->good_standing) {
            $pdf = Pdf::loadView('pdf.good_standing', [
                'nocData' => $nocData,
                'currentDate' => Carbon::now()->format('d-m-Y'),
                'qrCode' => $qrCodeBase64,
                'dob' => $dobFormatted,
                'images' => $imagePaths,
                'userId' => $userId,
                'applicant' => $applicant,
            ]);
        } else {
            $pdf = Pdf::loadView('pdf.noc_registration', [
                'nocData' => $nocData,
                'currentDate' => Carbon::now()->format('Y-m-d'),
                'qrCode' => $qrCodeBase64,
                'images' => $imagePaths,
                'userId' => $userId,
                'applicant' => $applicant,
            ]);
        }
        // Load a PDF view and pass the image paths to it
        // $pdf = PDF::loadView('pdf.images', ['images' => $imagePaths, 'userId' => $userId, 'applicant' => $applicant]);

        // Return the generated PDF for download
        return $pdf->download("{$applicant->title}_{$applicant->first_name}_{$applicant->last_name}.pdf");
    }
}
