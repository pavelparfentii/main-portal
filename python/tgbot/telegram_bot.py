from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup, KeyboardButton, ReplyKeyboardMarkup, WebAppInfo, MenuButtonWebApp, MenuButtonCommands
from telegram.constants import ParseMode
from telegram.ext import ApplicationBuilder, CommandHandler, MessageHandler, CallbackQueryHandler, filters, ContextTypes
from dotenv import load_dotenv
import logging
import uuid
import os


# Replace 'YOUR_BOT_TOKEN' with your actual bot token
# BOT_TOKEN = '7475491207:AAGA37IImPNWoZd_jsuAvmY7cgOiBhe_jGc'

load_dotenv()

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler("prod_bot.log"),
        logging.StreamHandler()
    ]
)

# set higher logging level for httpx to avoid all GET and POST requests being logged
logging.getLogger("httpx").setLevel(logging.WARNING)

logger = logging.getLogger(__name__)

BOT_TOKEN = os.getenv('TELEGRAM_BOT')
APP_ENV = os.getenv('APP_ENV', 'local')

if not BOT_TOKEN:
    print("No BOT_TOKEN found in environment variables")
else:
    print(BOT_TOKEN)


# Set URL based on APP_ENV
if APP_ENV == 'production':
    app_url = "https://tg-app.souls.club"
    chat_url = "https://t.me/soulsclubeth"
elif APP_ENV == 'staging':
    app_url = "https://tg-bot-staging.souls.club"
else:
    app_url = "https://tg-bot-staging.souls.club"
    chat_url ="https://t.me/+YqZWK8A9lV1iNTIy"  # Default to local

# prod
# BOT_TOKEN = '7133712001:AAF_-jyDA81HO9yPY9yErEPGq1g0-_gHky0'
started_users = set()

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    # Get the user's first name and last name

    user_id = update.message.from_user.id
    started_users.add(user_id)

    logger.info(f"User {user_id} has started the bot. Total started users: {len(started_users)}")

    user_first_name = update.message.from_user.first_name
    user_last_name = update.message.from_user.last_name

    #remove button get diamond
    #await context.bot.set_chat_menu_button(chat_id=update.message.chat_id, menu_button=MenuButtonCommands())

    # Create inline keyboard buttons
    inline_keyboard = [
        [InlineKeyboardButton("🎮 Launch app", web_app=WebAppInfo(url=app_url))],
        [InlineKeyboardButton("💬 Chat", url=chat_url)],
        [InlineKeyboardButton("💎 souls.club channel", url="https://t.me/soulsclub")],
        [InlineKeyboardButton("🌐 About souls.club", url="https://souls.club/airdrop/about")],
    ]

#     # Create the inline keyboard markup
    inline_reply_markup = InlineKeyboardMarkup(inline_keyboard)


    # Send the message with the inline keyboard
    await update.message.reply_text(
        f'gmgn {user_first_name} {user_last_name}! It\'s great to see you at Souls.Club! 💎 Your hub for earning Diamonds through mini-games and tasks!\n\n'
        'Check out our new Telegram mini app! Begin farming Diamonds now and enjoy your rewards! 🎁\n\n'
        'Got friends? Bring them along! The more, the merrier!',
        reply_markup=inline_reply_markup
    )


    # Create custom keyboard buttons
    custom_keyboard = [
            #
        [KeyboardButton("💬 Chat"), KeyboardButton("💎 souls.club channel")],
        [KeyboardButton("🌐 About souls.club")]
    ]
#
#     # Create the custom keyboard markup
    custom_reply_markup = ReplyKeyboardMarkup(custom_keyboard, resize_keyboard=True)
#
#     # Send another message with the custom keyboard
    await update.message.reply_text(
        text="Use the buttons below to navigate:",
        reply_markup=custom_reply_markup
    )

        #get diamond button
    web_app_info = WebAppInfo(url=app_url)
    menu_but = MenuButtonWebApp(text="get 💎", web_app=web_app_info)
    await context.bot.set_chat_menu_button(chat_id=update.message.chat_id, menu_button=menu_but)


async def handle_chat(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:

    keyboard = [
        [InlineKeyboardButton("Join our Chat", url=chat_url)]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)

    message = '<b>Join our Chat</b>: Connect with fellow members, share your experiences, and stay updated with the latest news. Plus, get insider tips on maximizing your crypto assets.'
    await update.message.reply_text(message, reply_markup=reply_markup, parse_mode=ParseMode.HTML)


async def handle_channel(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    keyboard = [
        [InlineKeyboardButton("Subscribe to the Channel", url="https://t.me/soulsclub")]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)

    message = '<b>Subscribe to our Channel</b>: Never miss an update! Get exclusive news, announcements, and behind-the-scenes content. Enjoy early access to special offers and promotions.'

    await update.message.reply_text(message, reply_markup=reply_markup, parse_mode=ParseMode.HTML)

async def handle_about(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:

    message = (
        '<b>Welcome to <a href="https://souls.club/airdrop/about">Souls.club</a> 🌟</b>\n\n'
        '<a href="https://souls.club">Souls.club</a> is a multi-level ecosystem that blends blockchain visualization, gaming, social security, and NFT mechanics. Our platform offers a range of innovative products:\n\n'
        '💎 <b>Digital Soul:</b> Experience dynamic blockchain visualization. Transform your social activity and wallet data into art as a decentralized ID.\n\n'
        '🦖 <b>Digital Animals NFTs:</b> Own unique generative artworks created by our SEO.\n\n'
        '🎮 <b>Digital Animals Game:</b> Embark on a meditative mobile adventure. Explore a utopian world governed by AI, where souls are embodied as animals.\n\n'
        '🔒 <b>SafeSoul:</b> Stay protected with our community-driven safety platform, which displays scam alerts on websites.\n\n'
        '🛍️ <b>Store:</b> Shop for exclusive <i>merch</i> in our dedicated department.\n\n'
        '<b>Join the <a href="https://souls.club/airdrop/about">Souls.club</a> family 👇</b>\n\n'
        '<a href="https://t.me/soulsclubeth">Chat</a> | <a href="https://twitter.com/soulsclub">Twitter</a> | <a href="https://discord.gg/soulsclub">Discord</a> | <a href="https://opensea.io/collection/soulsclub">OpenSea</a> | <a href="https://souls.club">Website</a>'
    )

    await update.message.reply_text(text=message, parse_mode=ParseMode.HTML)

async def handle_default(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    logger.info(f"Received default message from {update.message.from_user.id} ({update.message.from_user.username}): {update.message.text}")

    inline_keyboard = [
        [InlineKeyboardButton("🎮 Launch app", web_app=WebAppInfo(url=app_url))]
    ]
    inline_reply_markup = InlineKeyboardMarkup(inline_keyboard)

    await update.message.reply_text(
        text="Use the button below to launch the app:",
        reply_markup=inline_reply_markup
    )

#Get message from channel and group
async def handle_comments(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if update.message:
        user = update.message.from_user
        comment = update.message.text
        chat_id = update.message.chat_id


        # Log the comment or store it in a database
        print(f"User {user.username} ({user.id}) commented: {comment} in chat {chat_id}")

        # Reply to the comment
        # await update.message.reply_text(f"Thanks for your comment, {user.first_name}!")

        # Отправка POST-запроса на внешний эндпоинт

        data = {
                'user_id': user.id,
            }

        async with httpx.AsyncClient() as client:
            response = await client.post(EXTERNAL_ENDPOINT, json=data)

            # Проверка успешности запроса
        if response.status_code == 200:
                print("Successfully sent comment to external endpoint.")
        else:
                print(f"Failed to send comment. Status code: {response.status_code}")

    elif update.channel_post:
        channel_post = update.channel_post
        user = channel_post.sender_chat  # Channel posts often come from the channel itself

        # Log the channel post or store it in a database
        print(f"Channel {user.title} posted: {channel_post}")
    else:
        # Log that we received an update without a message (could be a different type of update)
        print("Received an update without a message.")



def main() -> None:

    application = ApplicationBuilder().token(BOT_TOKEN).build()
    application.add_handler(CommandHandler("start", start))
    # Register handlers for custom keyboard buttons
    # application.add_handler(MessageHandler(filters.TEXT & filters.Regex("🎮 Launch app"), handle_launch_app))
    application.add_handler(MessageHandler(filters.TEXT & filters.Regex("💬 Chat"), handle_chat))
    application.add_handler(MessageHandler(filters.TEXT & filters.Regex("💎 souls.club channel"), handle_channel))
    application.add_handler(MessageHandler(filters.TEXT & filters.Regex("🌐 About souls.club"), handle_about))
#     application.add_handler(MessageHandler(filters.TEXT & filters.Regex("💎 Get Diamond"), handle_get_diamond))

    application.add_handler(MessageHandler(filters.TEXT, handle_default))
    application.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, handle_comments))

    # Start the bot
    application.run_polling()

if __name__ == '__main__':
    main()
