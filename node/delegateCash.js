const wallet = process.argv[2];
import fetch from 'node-fetch';

async function main(){

    if (!wallet) {
        const message = {
            state: 'error',
            data: 'Please provide a wallet and address'
        };

        console.log(JSON.stringify(message));
        return;
    }

    try {

        const controller = new AbortController();
        const id = setTimeout(() => controller.abort(), 1000);

        const RPC_URL = await fetch(`https://api.delegate.xyz/registry/v2/${wallet}`, {
            signal:controller.signal,
            headers: {
                "X-API-KEY": "safesoul-7431-450f-87b5-5c46106c8fb9"
            }

        });
        clearTimeout(id);

        if (!RPC_URL.ok) {

            throw new Error(`Failed to fetch data. Status: ${RPC_URL.status}`);
        }

        const delegatesForContract = await RPC_URL.json();

        // console.log(delegatesForContract);

        const message = {
            state: 'success',
            data: delegatesForContract.length > 0 ? (delegatesForContract.some(item => item.type === 'ALL') ? delegatesForContract.find(item => item.type === 'ALL')['from'] : null) : null
        };

        console.log(JSON.stringify(message));
    } catch (error) {
        // Handle the error
        const errorMessage = {
            state: 'error',
            data: error.message
        };

        console.error(JSON.stringify(errorMessage));
    }
}

main();
