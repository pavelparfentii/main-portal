import Web3 from 'web3';

import pg from 'pg'
const { Client } = pg

console.log("Loaded");
// Ensure you are using the correct Web3 version
console.log(`Web3 version: ${Web3.version}`);

const web3 = new Web3(new Web3.providers.WebsocketProvider("wss://mainnet.infura.io/ws/v3/518edf6cce914599ac7baa22a919620d"));

// ABI for the ERC-721 Transfer event
const erc721TransferABI = [
    {
        "anonymous": false,
        "inputs": [
            {
                "indexed": true,
                "name": "from",
                "type": "address"
            },
            {
                "indexed": true,
                "name": "to",
                "type": "address"
            },
            {
                "indexed": true,
                "name": "tokenId",
                "type": "uint256"
            }
        ],
        "name": "Transfer",
        "type": "event"
    }
];

// PostgreSQL connection details
const client = new Client({
    user: 'sammy',
    host: '127.0.0.1', // Adjust if necessary
    database: 'portal_db',
    password: 'password',
    port: 5432, // Adjust if necessary
});

async function fetchTokensToMonitor() {
    await client.connect();
    const res = await client.query('SELECT contract_address AS "contractAddress", token_id AS "tokenId" FROM partner_n_f_t_s');
    await client.end();
    return res.rows;

}

// async function checkTokens() {
//     try {
//         const data = await fetchTokensToMonitor();
//         console.log(data);
//     } catch (error) {
//         console.error('Error fetching tokens:', error);
//     }
// }
//
// checkTokens();




web3.eth.net.isListening()
    .then(async () => {
        console.log('Successfully connected to WebSocket provider');

        // Fetch tokens to monitor from the database
        const tokensToMonitor = await fetchTokensToMonitor();

        // Subscribe to Transfer events for each token to monitor

        console.log('Tokens to monitor:', tokensToMonitor);

        tokensToMonitor.forEach(({ contractAddress, tokenId }) => {
            console.log(`Subscribing to contract: ${contractAddress}, tokenId: ${tokenId}`);
            const contract = new web3.eth.Contract(erc721TransferABI, contractAddress);

            if (!contract.events) {
                console.error(`Failed to create contract instance for address: ${contractAddress}`);
                return;
            }

            contract.events.Transfer({
                fromBlock: 'latest',
                filter: { tokenId } // Filter events by tokenId
            })
                .on('data', event => {
                    console.log(`Event received for contract: ${contractAddress}, tokenId: ${tokenId}`);
                    // console.log('Transfer event:', event.returnValues);
                    // Accessing specific event return values
                    const { from, to, tokenId } = event.returnValues;
                    if (from && to && tokenId) {
                        console.log(`Transfer event from contract ${contractAddress}:`, event.returnValues);
                        console.log(`NFT Transfer from ${from} to ${to} with tokenId ${tokenId}`);
                        // You can add additional processing here
                    } else {
                        //console.log('Empty or malformed event detected, skipping...');
                    }
                })
                .on('error', err => {
                    console.error('Error:', err);
                });
        });
    })
    .catch(e => {
        console.error('Connection error:', e);
    });

