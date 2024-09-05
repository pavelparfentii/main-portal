import web3 from "web3";

import contractABI from '../node/DAContractABI/contractABI.js';
import fetch from "node-fetch";

async function fetchApiKey() {
    const response = await fetch('https://safesoul.test-dev.site/api/airdrop/infuraKey');
    const data = await response.json(); // Assuming the key is returned in JSON format
    // console.log(data);

    return data.key; // Adjust according to your actual API response structure
}

async function getWeb3WithNextApiKey() {
    try {
        const nextApiKey = await fetchApiKey();
        return new web3(
            new web3.providers.HttpProvider(
                "https://mainnet.infura.io/v3/" + nextApiKey
            )
        );
    } catch (error) {
        console.error('Failed to create web3 instance with the next API key:', error);
        throw error;
    }
}

async function initializeContract() {
    const web3Instance = await getWeb3WithNextApiKey(); // Await the web3 instance
    const contract = new web3Instance.eth.Contract(
        contractABI,
        "0x25593A50255Bfb30eA027f6966417b0BF780401d"
    );
    return contract;
}

const contractAddress = '0x25593A50255Bfb30eA027f6966417b0BF780401d';


async function fetchLast100TransferEvents() {
    try {
        const web3Instance = await getWeb3WithNextApiKey(); // Make sure this is defined in your scope
        const contract = new web3Instance.eth.Contract(contractABI, contractAddress);

        // Fetch the latest block number
        const latestBlockNumber = Number(await web3Instance.eth.getBlockNumber());

        // Assuming a high transaction volume, adjust these numbers based on your contract's activity
        const fromBlock = Math.max(0, latestBlockNumber - 500000); // Example: Check the last 50,000 blocks
        const toBlock = 'latest';

        // Fetch events from the calculated range
        const events = await contract.getPastEvents('Transfer', {
            fromBlock,
            toBlock
        });

        const last100Events = events.length > 500 ? events.slice(-500) : events;

        // If more than 100 events are fetched, keep only the last 100
        for (const event of last100Events) {
            const txHash = event.transactionHash;
            const transaction = await web3Instance.eth.getTransaction(txHash);
            // console.log(transaction)
            // Assuming a sale involves Ether transfer, we check the transaction value
            if (transaction.value > 0) {
                const receiverAddress = event.returnValues.to;
                // const block = await web3Instance.eth.getBlock(transaction.blockNumber);
                const block = await web3Instance.eth.getBlock(transaction.blockNumber);
                const timestamp = Number(block.timestamp) * 1000;
                const date = new Date(timestamp);

                // console.log(`Sale for token ID ${event.returnValues.tokenId} received by account ${receiverAddress} in transaction ${txHash} `);
                // console.log(`Transaction Date: ${date.toUTCString()}`);

                const output = {
                    date: date.toUTCString(),
                    message:`token ID ${event.returnValues.tokenId} bought by account ${receiverAddress} in transaction ${txHash} `,
                    wallet: receiverAddress,
                    token: Number(event.returnValues.tokenId)
                    // This is now a single concatenated string of messages
                };
                // console.log(output);
                console.log(JSON.stringify(output));
            }
        }

    } catch (error) {
        console.error('Error fetching events:', error);
    }
}

fetchLast100TransferEvents();
