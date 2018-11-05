<?php

namespace vultrui\Http\Controllers;

use Illuminate\Support\Facades\Notification;
use vultrui\Notifications\KeyAdded;
use vultrui\Notifications\KeyDeleted;
use vultrui\Notifications\KeyUpdated;
use vultrui\VultrLib\Ssh;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use vultrui\User;
use Illuminate\Support\Facades\Auth;

class Sshkeys extends Controller
{

	protected $vultr;

	public function __construct(Ssh $vultr)

	{

		$this->vultr = $vultr;

	}

    public function index()
    {

    	$View = view('dash.ssh')->with( 'sshkeys', $this->vultr->list() );

        if ( array_key_exists( 'error', $this->vultr->list() ) ) {

            return view('errors.connection')->with('error' , $this->vultr->list()['error'] );
        }
        
        return $View;
    }

    public function add()
    {

        $View = view('modals.add-ssh');

        if ( array_key_exists( 'error', $this->vultr->list() ) ) {

            return view('errors.connection')->with('error' , $this->vultr->list()['error'] );
        }
        
        return $View;
    }

    public function create(Request $request) 
    {

        $user = User::findOrFail( Auth::id() );

        $data = [
            'name' => $request->name ,
            'ssh_key' => $request->ssh_key
        ];

        $results = $this->vultr->create( array(), $data );

        if ( !in_array('error', $results ) && isset( $results['SSHKEYID'] ) ) {

            // clear cache
            Cache::forget('sshkeys');

            $keyInfo = $this->vultr->list()[$results['SSHKEYID'] ];

            // $user->notify( new KeyAdded( $keyInfo ) );

            activity()->log( __( 'Creating new SSH Key' ) );

            // redirect and flush session
            return redirect('sshkeys')->with( ['type' => 'success', 'message' => 'SSH key created' ]);

        }

        if ( isset( $results['error'] ) )

            if (preg_match( '/response:\s(.*)/i', $results['error'], $matches) ) {

                return redirect('sshkeys/add')->with( ['type' => 'error', 'message' => str_replace('response:', null, $matches[0] ) ] );

            }

            return redirect('sshkeys/add')->with( ['type' => 'error', 'message' => $results['error'] ] );
    }

    public function edit( $sshkeyid )
    {

        if ( isset($this->vultr->list()[ $sshkeyid ] ) ) {

            return view('modals.add-ssh')->with([ 'sshkeyid' => $sshkeyid, 'sshkey' => $this->vultr->list() ]);

        }


        return redirect('sshkeys')->with( ['type' => 'error', 'message' => 'SSHKEY ID not found' ] );

    }


    public function update( Request $request )
    {

        $user = User::findOrFail( Auth::id() );

        $data = [

            'SSHKEYID' => $request->sshkeyid,
            'name'     => $request->name,
            'ssh_key'  => $request->ssh_key,

        ];

        $destroyRes = $this->vultr->update( array(), $data );

        if ( !isset( $destroyRes['error'] ) ) {

            // clear cache
            Cache::forget('sshkeys');

            $keyInfo = $this->vultr->list()[$results['SSHKEYID'] ];

            $user->notify( new KeyUpdated( $keyInfo ) );

            activity()->log( __( 'Updating SSH key' ) );

            return redirect('sshkeys')->with( ['type' => 'success', 'message' => 'SSH key <strong>'.$request->sshkeyid.'</strong> updated' ]);

        } else {

            if (preg_match( '/response:\s(.*)/i', $destroyRes['error'], $matches) ) {

                return redirect('sshkeys')->with( ['type' => 'error', 'message' => str_replace('response:', null, $matches[0] ) ] );
                
            }

        }

        return redirect('snapshots')->with( ['type' => 'error', 'message' => $destroyRes['error'] ] );
    }


    public function destroy(Request $request) 
    {

        $user = User::findOrFail( Auth::id() );

        $data = [
            'SSHKEYID' => $request->sshkeyid,
        ];

        $destroyRes = $this->vultr->destroy( array(), $data );

        if ( !isset( $destroyRes['error'] ) ) {

            // clear cache
            Cache::forget('sshkeys');

            $user->notify( new KeyDeleted( $request->sshkeyid ) );

            // redirect and flush session
            return redirect('sshkeys')->with( ['type' => 'success', 'message' => 'SSH key <strong>'.$request->sshkeyid.'</strong> deleted' ]);

        } else {

            if (preg_match( '/response:\s(.*)/i', $destroyRes['error'], $matches) ) {

                return redirect('sshkeys')->with( ['type' => 'error', 'message' => str_replace('response:', null, $matches[0] ) ] );

            }

        }

        return redirect('snapshots')->with( ['type' => 'error', 'message' => $destroyRes['error'] ] );

    }




}