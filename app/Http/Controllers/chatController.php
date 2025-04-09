<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Thread;

class chatController extends Controller
{
    public function receive(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'runID' => 'nullable|string',
            'threadID' => 'nullable|string',
        ]);

        $message = $request->message;
        $jobs = $this->getJobsFromFeed();
        $assistantId = env('OPENAI_ASSISTANT_ID');

        $threadID = $request->threadID ?? '';

        if (empty($threadID)) {
            $thread = $this->createThread();
            $threadID = $thread->id;
            Thread::create([
                'thread_id' => $threadID,
            ]);
        }
        $this->createMessage($message, $threadID);

        $run = OpenAI::threads()->runs()->create($threadID, [
            'assistant_id' => $assistantId,
            'instructions' => "When a user mentions they are hiring or looking for labor (e.g., 'I need to hire an electrician, need skilled worker, need honest labour'), respond with the following staffing inquiry details:\n\nPhone: (855) 756-9675\nHours: Mon-Sun, 8 AM – 5 PM\nEmail: info@myqlm.com\n\nAlternatively, provide the link for submitting requirements: https://myqlm.com/contact/",
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'fetch_job_feed',
                        'description' => 'Fetches job listings from an external XML feed and returns them as JSON.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'jobs' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'title' => ['type' => 'string'],
                                            'location' => ['type' => 'string'],
                                            'description' => ['type' => 'string'],
                                            'link' => ['type' => 'string']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        $responseCheck = true;
        while ($responseCheck) {
            $runStatus = OpenAI::threads()->runs()->retrieve($threadID, $run->id);
            Log::info('Run Status', [$runStatus]);
            if ($runStatus->status === 'requires_action') {
                OpenAI::threads()->runs()->submitToolOutputs($threadID, $run->id, [
                    'tool_outputs' => [
                        [
                            'tool_call_id' => $runStatus->requiredAction->submitToolOutputs->toolCalls[0]->id,
                            'output' => json_encode(['jobs' => $jobs]),
                        ]
                    ]
                ]);
                //$responseCheck = false;
            } elseif ($runStatus->status === 'completed') {
                $messages = OpenAI::threads()->messages()->list($threadID);
                $assistantResponse = collect($messages->data)
                    ->where('role', 'assistant')
                    ->pluck('content')
                    ->first();
                Log::info("Messages", [$messages]);
                return response()->json([
                    'message' => $assistantResponse,
                    'runID' => $run->id,
                    'threadID' => $threadID
                ]);
            }
        }
        $messages = OpenAI::threads()->messages()->list($threadID);
        $assistantResponse = collect($messages->data)
            ->where('role', 'assistant')
            ->pluck('content')
            ->first();
        Log::info("Messages", [$messages]);
        return response()->json([
            'message' => $assistantResponse,
            'runID' => $run->id,
            'threadID' => $threadID
        ]);
    }

    private function createThread()
    {
        $thread = OpenAI::threads()->create([]);
        Log::info('Thread created', [$thread]);
        return $thread;
    }

    private function createMessage($message, $threadId)
    {
        $createdMessage = OpenAI::threads()->messages()->create($threadId, [
            'role' => 'user',
            'content' => $message,
        ]);
        Log::info('Message Sent', [$createdMessage]);
    }

    public function modelTest(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $message = $request->message;
        $jobs = $this->getJobsFromFeed();
        $assistantId = env('OPENAI_ASSISTANT_ID');

        $thread = $this->createThread();
        $this->createMessage($message, $threadID);

        $run = OpenAI::threads()->runs()->create($threadID, [
            'assistant_id' => $assistantId,
            'instructions' => "You must use the `fetch_job_feed` tool to retrieve jobs before responding. Do not answer without fetching the job feed.",
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'fetch_job_feed',
                        'description' => 'Fetches job listings from an external XML feed and returns them as JSON.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'jobs' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'title' => ['type' => 'string'],
                                            'location' => ['type' => 'string'],
                                            'description' => ['type' => 'string'],
                                            'link' => ['type' => 'string']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        Log::info('Run created', [$run]);
        $responseCheck = true;
        while ($responseCheck) {
            sleep(2);
            $runStatus = OpenAI::threads()->runs()->retrieve($threadID, $run->id);
            Log::info('Run Status', [$runStatus]);
            if ($runStatus->status === 'requires_action') {
                OpenAI::threads()->runs()->submitToolOutputs($threadID, $run->id, [
                    'tool_outputs' => [
                        [
                            'tool_call_id' => $runStatus->requiredAction->submitToolOutputs->toolCalls[0]->id,
                            'output' => json_encode(['jobs' => $jobs]),
                        ]
                    ]
                ]);
                $responseCheck = false;
            } elseif ($runStatus->status === 'completed') {
                $messages = OpenAI::threads()->messages()->list($threadID);
                $assistantResponse = collect($messages->data)
                    ->where('role', 'assistant')
                    ->pluck('content')
                    ->first();
                Log::info("Messages", [$messages]);
                return response()->json([
                    'result' => $assistantResponse
                ]);
            }
        }
        $messages = OpenAI::threads()->messages()->list($threadID);
        $assistantResponse = collect($messages->data)
            ->where('role', 'assistant')
            ->pluck('content')
            ->first();
        Log::info("Messages", [$messages]);
        return response()->json([
            'result' => $assistantResponse
        ]);
    }

    public function getJobsFromFeed()
    {
        $url = 'https://myqlm.my.salesforce-sites.com/XMLFeedPage';

        return Cache::remember('jobs_feed', now()->addMinutes(5), function () use ($url) {
            try {
                $response = Http::get($url);
                if ($response->failed()) return [];

                $xml = simplexml_load_string($response->body());
                $jobs = [];

                foreach ($xml->job as $job) {
                    $jobs[] = [
                        'title' => (string) $job->{'job-title'},
                        'location' => (string) $job->city . ', ' . (string) $job->state,
                        'description' => strip_tags((string) $job->{'job-description'}),
                        'link' => "https://myqlm.com/open-positions/",
                    ];
                }

                return $jobs;
            } catch (\Exception $e) {
                return [];
            }
        });
    }
}
