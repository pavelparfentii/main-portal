import Web3 from 'web3';

const contractAddress = process.argv[2]; // The third element in process.argv is the contract address

console.log(`Started monitoring ERC-1155 contract: ${contractAddress}`);

const web3 = new Web3(new Web3.providers.WebsocketProvider("wss://mainnet.infura.io/ws/v3/40892bf6b52d4e1b81daf162c9029d93"));

// ABI for the ERC-1155 Transfer event
const erc1155TransferABI = [
    {
        "anonymous": false,
        "inputs": [
            {
                "indexed": true,
                "name": "operator",
                "type": "address"
            },
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
                "indexed": false,
                "name": "id",
                "type": "uint256"
            },
            {
                "indexed": false,
                "name": "value",
                "type": "uint256"
            }
        ],
        "name": "TransferSingle",
        "type": "event"
    },
    {
        "anonymous": false,
        "inputs": [
            {
                "indexed": true,
                "name": "operator",
                "type": "address"
            },
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
                "indexed": false,
                "name": "ids",
                "type": "uint256[]"
            },
            {
                "indexed": false,
                "name": "values",
                "type": "uint256[]"
            }
        ],
        "name": "TransferBatch",
        "type": "event"
    }
];

const contract = new web3.eth.Contract(erc1155TransferABI, contractAddress);

// Calculate the block number for 2 days ago
// Calculate the block number for 2 days ago
const twoDaysAgo = Math.floor(Date.now() / 1000) - (2 * 24 * 60 * 60);

web3.eth.getBlockNumber()
    .then(latestBlock => {
        console.log('Latest Block:', latestBlock);

        // Query TransferSingle events from the last 2 days
        contract.getPastEvents('TransferSingle', {
            fromBlock: 19928417, // approximately 2 days ago (assuming 15s block time)
            toBlock: 'latest'
        }).then(events => {
            console.log('Past TransferSingle events:');
            if (events.length === 0) {
                console.log('No TransferSingle events found in the past 2 days.');
            } else {
                events.forEach(event => {
                    const { operator, from, to, id, value } = event.returnValues;
                    console.log(`Single Transfer from ${from} to ${to} with tokenId ${id} and value ${value}`);
                });
            }
        }).catch(err => {
            console.error('Error fetching past TransferSingle events:', err);
        });

        // Query TransferBatch events from the last 2 days
        contract.getPastEvents('TransferBatch', {
            fromBlock: latestBlock - 5760, // approximately 2 days ago (assuming 15s block time)
            toBlock: 'latest'
        }).then(events => {
            console.log('Past TransferBatch events:');
            if (events.length === 0) {
                console.log('No TransferBatch events found in the past 2 days.');
            } else {
                events.forEach(event => {
                    const { operator, from, to, ids, values } = event.returnValues;
                    console.log(`Batch Transfer from ${from} to ${to} with tokenIds ${ids} and values ${values}`);
                });
            }
        }).catch(err => {
            console.error('Error fetching past TransferBatch events:', err);
        });
    })
    .catch(e => {
        console.error('Connection error:', e);
    });

// web3.eth.net.isListening()
//     .then(() => {
//         console.log('Successfully connected to WebSocket provider');
//
//         // Subscribe to TransferSingle events for the ERC-1155 contract
//         contract.events.TransferSingle({
//             fromBlock: 'latest'
//         })
//             .on('data', event => {
//                 console.log('TransferSingle event:', event.returnValues);
//                 const { operator, from, to, id, value } = event.returnValues;
//                 console.log(`Single Transfer from ${from} to ${to} with tokenId ${id} and value ${value}`);
//             })
//             .on('error', err => {
//                 console.error('Error:', err);
//             });
//
//         // Subscribe to TransferBatch events for the ERC-1155 contract
//         contract.events.TransferBatch({
//             fromBlock: 'latest'
//         })
//             .on('data', event => {
//                 console.log('TransferBatch event:', event.returnValues);
//                 const { operator, from, to, ids, values } = event.returnValues;
//                 console.log(`Batch Transfer from ${from} to ${to} with tokenIds ${ids} and values ${values}`);
//             })
//             .on('error', err => {
//                 console.error('Error:', err);
//             });
//     })
//     .catch(e => {
//         console.error('Connection error:', e);
//     });
