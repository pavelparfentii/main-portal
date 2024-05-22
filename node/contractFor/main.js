import Web3 from 'web3';

const contractAddress = process.argv[2]; // The third element in process.argv is the contract address

console.log(`Started monitoring contract: ${contractAddress} in process PID: ${process.pid}`);

const web3 = new Web3(new Web3.providers.WebsocketProvider("wss://mainnet.infura.io/ws/v3/518edf6cce914599ac7baa22a919620d"));

web3.currentProvider.on('error', (error) => {
    console.error(`WebSocket error for contract ${contractAddress} in process PID: ${process.pid}`, error);
});

web3.currentProvider.on('end', (error) => {
    console.error(`WebSocket connection ended for contract ${contractAddress} in process PID: ${process.pid}`, error);
});

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

const contract = new web3.eth.Contract(erc721TransferABI, contractAddress);

web3.eth.net.isListening()
    .then(() => {
        console.log(`Successfully connected to WebSocket provider in process PID: ${process.pid}`);

        // Subscribe to Transfer events for the NFT contract
        contract.events.Transfer({
            fromBlock: 'latest'
        })
            .on('data', event => {
                console.log(`Transfer event for contract ${contractAddress} in process PID: ${process.pid}`, event.returnValues);
                // Accessing specific event return values
                const { from, to, tokenId } = event.returnValues;
                console.log(`NFT Transfer from ${from} to ${to} with tokenId ${tokenId}`);
                // You can add additional processing here
            })
            .on('error', err => {
                console.error(`Error for contract ${contractAddress} in process PID: ${process.pid}`, err);
            });
    })
    .catch(e => {
        console.error(`Connection error in process PID: ${process.pid}`, e);
    });
