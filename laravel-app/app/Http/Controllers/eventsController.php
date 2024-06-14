<?php

namespace App\Http\Controllers;

use App\Models\Events;
use App\Models\Tickets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class eventsController extends Controller
{

    public function createEvent(Request $request)
    {
        try {
            // Validate incoming request data
            $validatedData = $request->validate([
                'name' => 'required|string',
                'capacity' => 'required|integer',
                'venue' => 'required|string',
                'ticket_price' => 'required|numeric',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image file
            ]);
            
            // Get authenticated user details
            $user = Auth::user();
    
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images'), $imageName); // Move uploaded image to public/images directory
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Image file is required',
                ], 400);
            }
    
            // Create new event instance
            $event = new Events();
            $event->user_id = 1;
            $event->org_id = 12;
            $event->name = $validatedData['name'];
            $event->event_id = uniqid(); // Generate a unique event ID
            $event->event_date = date('Y-m-d');
            $event->capacity = $validatedData['capacity'];
            $event->venue = $validatedData['venue'];
            $event->time = date('H:i:s');
            $event->ticket_price = $validatedData['ticket_price'];
            $event->qr_code = uniqid(); // Generate a unique QR code
            $event->image = $imageName; // Save image URL to database
    
            // Save the event to the database
            $event->save();
    
            // Generate tickets for the event
            $this->generateTicketsForEvent($event);
    
            return response()->json([
                'status' => true,
                'message' => 'Event created successfully',
                'event' => $event
            ], 201); // HTTP status code 201 for resource created
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500); // Internal Server Error
        }
    }
    

    private function generateTicketsForEvent(Events $event)
    {
        // Generate tickets based on event capacity
        $tickets = [];
        for ($i = 1; $i <= $event->capacity; $i++) {
            $ticket = new Tickets();
            $ticket->name = $event->name;
            $ticket->event_id = $event->event_id;
            $ticket->type = 'General Admission'; // You can define ticket types as needed
            $ticket->qr_code = uniqid(); // Generate unique QR code for each ticket
            $ticket->validated = false; // Ticket starts as not validated
            $ticket->purchased= false;//ticket not purchased yet
            $ticket->price = $event->ticket_price; // Set initial price (can be updated later if needed)
            $ticket->save();
            $tickets[] = $ticket;
        }

        return $tickets;
    }


    public function fetchEvents(){
        try{

            $user = Auth::user();

            $events = Events::all();

            foreach ($events as $event) {
                $event->image = asset('images/' . $event->image); // Prepend base URL to image URL
            }

            return response()->json([
                'status'=> true,
                'message'=>'events fetched successfully',
                'events' => $events
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function fetchSingleEvent($id){
        try{

            $event = Events::where('id', $id)->first();

            $event->image = asset('images/' . $event->image); 

            return response()->json([
                'status'=> true,
                'message'=>'event fetched successfully',
                'events' => $event
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function fetchEventUsers($id){
        try{
            // Find the event by its ID
            $event = Events::findOrFail($id);
    
            // Fetch users who have purchased tickets for the event
            $users = Tickets::where('event_id', $event->event_id)
                            ->whereNotNull('first_name') // Filter users with filled first name
                            ->whereNotNull('last_name') // Filter users with filled last name
                            ->where('purchased', 1) // Filter purchased tickets
                            ->get();
    
            return response()->json([
                'status'=> true,
                'message'=>'Users fetched successfully',
                'users' => $users
            ]);
    
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    

    public function editEvent(Request $request){
        try{

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

}
