import Web3 from 'web3';

const contractAddress = process.argv[3]; // The third element in process.argv is the contract address
const infura = process.argv[2]; // The third element in process.argv is the contract address

//console.log(`Started monitoring contract: ${contractAddress}`);

const web3 = new Web3(new Web3.providers.WebsocketProvider(`wss://mainnet.infura.io/ws/v3/${infura}`));

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
        //console.log('Successfully connected to WebSocket provider');

        // Subscribe to Transfer events for the NFT contract
        contract.events.Transfer({
            fromBlock: 'latest'
        })
            .on('data', event => {
                // console.log(contractAddress);
                // console.log('Transfer event:', event.returnValues);
                // Accessing specific event return values
                const { from, to, tokenId} = event.returnValues;

                const output = {
                    contractAddress: contractAddress,
                    from: from,
                    to: to,
                    token: Number(tokenId),

                    // This is now a single concatenated string of messages
                };
                // console.log(output);
                console.log(JSON.stringify(output));

                // console.log(`NFT Transfer from ${from} to ${to} with tokenId ${tokenId}`);
                // You can add additional processing here
            });
    })
    .catch(e => {
        console.error('Connection error:', e);
    });
